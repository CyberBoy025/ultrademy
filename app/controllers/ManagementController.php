<?php
declare(strict_types=1);

/**
 * Management dashboards and reporting — README §32, §38, and the §16 centre comparison.
 *
 * Everything here is centre-scoped through Auth::scopeCentres(), the same resolver the
 * rest of the system uses. A reporting tool with its own access model is how data leaks:
 * a centre manager running "students by centre" gets their centre, not everyone's.
 */
final class ManagementController
{
    private const PERMISSION = 'management.report.view';

    public static function dashboard(): void
    {
        Auth::requirePermission(self::PERMISSION);
        [$from, $to] = self::range();
        $centreIds = Auth::scopeCentres(self::PERMISSION);

        $main = View::render('management/dashboard', [
            'from'      => $from,
            'to'        => $to,
            'scoped'    => $centreIds !== null,
            'headline'  => ManagementReport::headline($centreIds, $from, $to),
            'growth'    => ManagementReport::monthlyGrowth(12),
            'funnel'    => ManagementReport::admissionsFunnel($centreIds, $from, $to),
            'centres'   => $centreIds === null ? ManagementReport::centreComparison($from, $to) : [],
            'services'  => $centreIds === null ? ManagementReport::serviceRollup($from, $to) : null,
            'atRisk'    => ManagementReport::atRiskStudents($centreIds, $from, $to),
        ]);
        View::shell('management', 'Management Overview', $main);
    }

    /** §16 — Gwagwalada vs Kubwa vs online, side by side. Unscoped viewers only. */
    public static function centres(): void
    {
        Auth::requirePermission(self::PERMISSION);
        if (Auth::scopeCentres(self::PERMISSION) !== null) {
            // A centre manager comparing centres would be reading another centre's
            // operational data — §42's exact prohibition. They get their own dashboard.
            Session::flash('error', 'Centre comparison is available to management, across all centres.');
            header('Location: app.php?r=management');
            exit;
        }
        [$from, $to] = self::range();

        $main = View::render('management/centres', [
            'from'    => $from,
            'to'      => $to,
            'rows'    => ManagementReport::centreComparison($from, $to),
        ]);
        View::shell('management', 'Centre Comparison', $main);
    }

    public static function academic(): void
    {
        Auth::requirePermission(self::PERMISSION);
        [$from, $to] = self::range();
        $centreIds = Auth::scopeCentres(self::PERMISSION);

        $main = View::render('management/academic', [
            'from'        => $from,
            'to'          => $to,
            'programmes'  => ManagementReport::programmePerformance($centreIds),
            'assessments' => ManagementReport::assessmentOutcomes(),
            'instructors' => ManagementReport::instructorLoad($centreIds, $from, $to),
            'atRisk'      => ManagementReport::atRiskStudents($centreIds, $from, $to),
        ]);
        View::shell('management', 'Academic Performance', $main);
    }

    /**
     * CSV export.
     *
     * Audited, because a full export of student names and attendance is a
     * data-protection event — §38 requires exports to be recorded.
     */
    public static function export(): void
    {
        Auth::requirePermission(self::PERMISSION);
        [$from, $to] = self::range();
        $centreIds = Auth::scopeCentres(self::PERMISSION);
        $what = (string) ($_GET['what'] ?? 'centres');

        [$filename, $header, $rows] = match ($what) {
            'programmes' => [
                'programme-performance',
                ['Programme', 'Mode', 'Enrolments', 'Active', 'Completed', 'Withdrawn', 'Completion %'],
                array_map(static function (array $p): array {
                    $total = (int) $p['enrolments'];
                    return [
                        $p['title'], $p['delivery_mode'], $total,
                        (int) $p['active'], (int) $p['completed'], (int) $p['withdrawn'],
                        $total > 0 ? round(((int) $p['completed']) * 100 / $total, 1) : '',
                    ];
                }, ManagementReport::programmePerformance($centreIds)),
            ],
            'at-risk' => [
                'at-risk-students',
                ['Student No', 'Name', 'Programme', 'Sessions marked', 'Attendance %'],
                array_map(static fn(array $r): array => [
                    $r['student_no'], $r['name'], $r['programme'], (int) $r['marked'], $r['rate'],
                ], ManagementReport::atRiskStudents($centreIds, $from, $to)),
            ],
            'instructors' => [
                'instructor-load',
                ['Instructor', 'Class groups', 'Sessions', 'Students'],
                array_map(static fn(array $r): array => [
                    $r['name'], (int) $r['class_groups'], (int) $r['sessions'], (int) $r['students'],
                ], ManagementReport::instructorLoad($centreIds, $from, $to)),
            ],
            default => [
                'centre-comparison',
                ['Centre', 'Active students', 'New enrolments', 'Revenue', 'Expenses', 'Net', 'Attendance %'],
                array_map(static fn(array $r): array => [
                    $r['name'], (int) $r['students'], (int) $r['enrolments'],
                    Money::toMajorString((int) $r['revenue']),
                    Money::toMajorString((int) $r['expenses']),
                    Money::toMajorString((int) $r['net']),
                    $r['attendance'] ?? 'n/a',
                ], ManagementReport::centreComparison($from, $to)),
            ],
        };

        Audit::log('management.exported', 'reports', 0, null,
            ['report' => $what, 'from' => $from, 'to' => $to, 'rows' => count($rows)]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ultrademy-' . $filename . '-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /**
     * The reporting period. Defaults to the last 90 days.
     *
     * Dates are validated rather than passed through: they reach SQL as bound parameters,
     * but a malformed date silently returns an empty report, which reads as "no activity"
     * rather than "your filter is broken".
     *
     * @return array{0:string,1:string}
     */
    private static function range(): array
    {
        $from = self::validDate((string) ($_GET['from'] ?? '')) ?? date('Y-m-d', strtotime('-90 days'));
        $to   = self::validDate((string) ($_GET['to'] ?? ''))   ?? date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        return [$from, $to];
    }

    private static function validDate(string $value): ?string
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return ($d !== false && $d->format('Y-m-d') === $value) ? $value : null;
    }
}
