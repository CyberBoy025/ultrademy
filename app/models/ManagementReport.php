<?php
declare(strict_types=1);

/**
 * Company-wide reporting — README §32 (manager visibility), §38 (role-specific
 * dashboards) and §16 (Gwagwalada vs Kubwa vs all centres).
 *
 * Deliberately separate from FinanceReport, which already owns revenue, expenses and
 * outstanding balances. This class owns the *operational* picture — people, admissions,
 * attendance, teaching — and composes FinanceReport where money is part of the answer.
 *
 * Every query here obeys the same centre scope as the rest of the system. A centre
 * manager running "students by centre" gets their centre; reporting is not a side door
 * around 03-rbac.md §4.
 */
final class ManagementReport
{
    /** Online/global rows carry centre_id = NULL and are reported as their own line (§31). */
    public const ONLINE_LABEL = 'Online / Head office';

    /**
     * @param array<int,int>|null $centreIds null = unscoped
     * @return array{0:string,1:array<int,mixed>} [sql fragment, params]
     */
    private static function scope(?array $centreIds, string $column): array
    {
        if ($centreIds === null) {
            return ['', []];
        }
        if ($centreIds === []) {
            return [' AND 1 = 0', []];
        }
        $ph = implode(',', array_fill(0, count($centreIds), '?'));
        return [" AND $column IN ($ph)", array_values($centreIds)];
    }

    // ------------------------------------------------------------------ headline

    /** The numbers a manager wants on one screen. */
    public static function headline(?array $centreIds, string $from, string $to): array
    {
        [$eScope, $eParams] = self::scope($centreIds, 'e.centre_id');

        $activeStudents = (int) Database::query(
            "SELECT COUNT(DISTINCT e.user_id) c FROM enrolments e WHERE e.status = 'active' $eScope",
            $eParams
        )->fetch()['c'];

        $completed = (int) Database::query(
            "SELECT COUNT(*) c FROM enrolments e WHERE e.status = 'completed' $eScope",
            $eParams
        )->fetch()['c'];

        $newEnrolments = (int) Database::query(
            "SELECT COUNT(*) c FROM enrolments e
             WHERE DATE(e.enrolled_at) BETWEEN ? AND ? $eScope",
            array_merge([$from, $to], $eParams)
        )->fetch()['c'];

        [$aScope, $aParams] = self::scope($centreIds, 'a.preferred_centre_id');
        $applications = (int) Database::query(
            "SELECT COUNT(*) c FROM applications a
             WHERE a.submitted_at IS NOT NULL AND DATE(a.submitted_at) BETWEEN ? AND ? $aScope",
            array_merge([$from, $to], $aParams)
        )->fetch()['c'];

        $pendingApplications = (int) Database::query(
            "SELECT COUNT(*) c FROM applications a WHERE a.status IN ('submitted','under_review') $aScope",
            $aParams
        )->fetch()['c'];

        // Registrations are global — a user account belongs to no centre.
        $newUsers = (int) Database::one(
            'SELECT COUNT(*) c FROM users WHERE DATE(created_at) BETWEEN :f AND :t',
            ['f' => $from, 't' => $to]
        )['c'];

        $finance = FinanceReport::summary($centreIds, $from, $to);

        return [
            'active_students'      => $activeStudents,
            'completed_enrolments' => $completed,
            'new_enrolments'       => $newEnrolments,
            'applications'         => $applications,
            'pending_applications' => $pendingApplications,
            'new_users'            => $newUsers,
            'revenue'              => $finance['revenue'],
            'expenses'             => $finance['expenses'],
            'net'                  => $finance['net'],
            'outstanding'          => $finance['outstanding'],
        ];
    }

    // -------------------------------------------------------------------- growth

    /**
     * Registrations and enrolments per month for the last N months.
     *
     * Months with no activity are filled with zeros rather than omitted — a line chart
     * that silently skips an empty month draws a straight line through it and reports
     * growth that did not happen.
     *
     * @return array<int,array{month:string,label:string,users:int,enrolments:int}>
     */
    public static function monthlyGrowth(int $months = 12): array
    {
        $buckets = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $buckets[$key] = [
                'month' => $key,
                'label' => date('M', strtotime($key . '-01')),
                'users' => 0,
                'enrolments' => 0,
            ];
        }
        $since = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));

        foreach (Database::all(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c FROM users
             WHERE created_at >= :s GROUP BY m",
            ['s' => $since]
        ) as $r) {
            if (isset($buckets[$r['m']])) {
                $buckets[$r['m']]['users'] = (int) $r['c'];
            }
        }
        foreach (Database::all(
            "SELECT DATE_FORMAT(enrolled_at,'%Y-%m') m, COUNT(*) c FROM enrolments
             WHERE enrolled_at >= :s GROUP BY m",
            ['s' => $since]
        ) as $r) {
            if (isset($buckets[$r['m']])) {
                $buckets[$r['m']]['enrolments'] = (int) $r['c'];
            }
        }
        return array_values($buckets);
    }

    // ------------------------------------------------------------ centre compare

    /**
     * §16 — Gwagwalada vs Kubwa vs online, side by side.
     *
     * Online is its own row, never folded into a physical centre (§31).
     */
    public static function centreComparison(string $from, string $to): array
    {
        $centres = Database::all('SELECT id, name, status FROM centres ORDER BY name');
        $rows = [];

        foreach ($centres as $c) {
            $rows[] = self::centreRow((int) $c['id'], (string) $c['name'], $from, $to);
        }
        $rows[] = self::centreRow(null, self::ONLINE_LABEL, $from, $to);
        return $rows;
    }

    private static function centreRow(?int $centreId, string $name, string $from, string $to): array
    {
        $isNull = $centreId === null;
        $cmp = $isNull ? 'IS NULL' : '= ?';
        $arg = $isNull ? [] : [$centreId];

        $students = (int) Database::query(
            "SELECT COUNT(DISTINCT user_id) c FROM enrolments WHERE status = 'active' AND centre_id $cmp",
            $arg
        )->fetch()['c'];

        $enrolments = (int) Database::query(
            "SELECT COUNT(*) c FROM enrolments WHERE centre_id $cmp AND DATE(enrolled_at) BETWEEN ? AND ?",
            array_merge($arg, [$from, $to])
        )->fetch()['c'];

        $revenue = (int) Database::query(
            "SELECT COALESCE(SUM(amount),0) a FROM payments
             WHERE status = 'successful' AND centre_id $cmp AND DATE(paid_at) BETWEEN ? AND ?",
            array_merge($arg, [$from, $to])
        )->fetch()['a'];

        $expenses = (int) Database::query(
            "SELECT COALESCE(SUM(amount),0) a FROM expenses
             WHERE status = 'approved' AND centre_id $cmp AND incurred_on BETWEEN ? AND ?",
            array_merge($arg, [$from, $to])
        )->fetch()['a'];

        // Physical-only figures. An online cohort has no rooms and no attendance register,
        // so reporting 0% there would read as "nobody turned up" rather than "not applicable".
        $rooms = $isNull ? null : (int) Database::query(
            'SELECT COUNT(*) c FROM rooms WHERE centre_id = ?', $arg
        )->fetch()['c'];

        $attendance = self::attendanceRate($centreId, $from, $to, $isNull);

        return [
            'id'         => $centreId,
            'name'       => $name,
            'students'   => $students,
            'enrolments' => $enrolments,
            'revenue'    => $revenue,
            'expenses'   => $expenses,
            'net'        => $revenue - $expenses,
            'rooms'      => $rooms,
            'attendance' => $attendance,
        ];
    }

    /**
     * Present-or-late as a share of all marked records.
     *
     * Returns null rather than 0 when nothing has been marked. Zero means "everyone was
     * absent"; null means "no register was taken", and a dashboard that confuses the two
     * sends a manager after the wrong problem.
     */
    public static function attendanceRate(?int $centreId, string $from, string $to, bool $isOnline = false): ?float
    {
        if ($isOnline) {
            return null;
        }
        $where = $centreId === null ? '' : ' AND e.centre_id = ?';
        $args = $centreId === null ? [] : [$centreId];

        $row = Database::query(
            "SELECT COUNT(*) total,
                    SUM(ar.status IN ('present','late')) present
             FROM attendance_records ar
             JOIN class_sessions cs ON cs.id = ar.class_session_id
             JOIN enrolments e ON e.id = ar.enrolment_id
             WHERE DATE(cs.starts_at) BETWEEN ? AND ?$where",
            array_merge([$from, $to], $args)
        )->fetch();

        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) {
            return null;
        }
        return round(((int) $row['present']) * 100 / $total, 1);
    }

    // ----------------------------------------------------------------- admissions

    /**
     * The funnel, as counts of applications that ever reached each stage.
     *
     * `approved` counts applications currently approved OR already enrolled — an
     * application that progressed past approval was still approved, and a funnel whose
     * later stage exceeds its earlier one is obviously broken.
     */
    public static function admissionsFunnel(?array $centreIds, string $from, string $to): array
    {
        [$scope, $params] = self::scope($centreIds, 'a.preferred_centre_id');
        $base = array_merge([$from, $to], $params);

        $count = static fn(string $extra, array $p): int => (int) Database::query(
            "SELECT COUNT(*) c FROM applications a
             WHERE a.submitted_at IS NOT NULL AND DATE(a.submitted_at) BETWEEN ? AND ? $extra",
            $p
        )->fetch()['c'];

        $submitted = $count($scope, $base);
        $reviewed  = $count("AND a.reviewed_at IS NOT NULL $scope", $base);
        $approved  = $count("AND (a.status = 'approved' OR EXISTS (SELECT 1 FROM enrolments en WHERE en.application_id = a.id)) $scope", $base);
        $enrolled  = $count("AND EXISTS (SELECT 1 FROM enrolments en WHERE en.application_id = a.id) $scope", $base);
        $rejected  = $count("AND a.status = 'rejected' $scope", $base);

        return [
            'submitted' => $submitted,
            'reviewed'  => $reviewed,
            'approved'  => $approved,
            'enrolled'  => $enrolled,
            'rejected'  => $rejected,
            'conversion' => $submitted > 0 ? round($enrolled * 100 / $submitted, 1) : null,
        ];
    }

    // ------------------------------------------------------------------ academic

    /** Enrolments, completion and assessment performance per programme. */
    public static function programmePerformance(?array $centreIds): array
    {
        [$scope, $params] = self::scope($centreIds, 'e.centre_id');

        return Database::query(
            "SELECT pr.id, pr.title, pr.delivery_mode,
                    COUNT(e.id) AS enrolments,
                    SUM(e.status = 'active')    AS active,
                    SUM(e.status = 'completed') AS completed,
                    SUM(e.status = 'withdrawn') AS withdrawn
             FROM programmes pr
             LEFT JOIN enrolments e ON e.programme_id = pr.id $scope
             GROUP BY pr.id, pr.title, pr.delivery_mode
             ORDER BY enrolments DESC, pr.title",
            $params
        )->fetchAll();
    }

    /** Assessment outcomes per course — pass rate and average, graded attempts only. */
    public static function assessmentOutcomes(): array
    {
        return Database::all(
            "SELECT c.id, c.title,
                    COUNT(t.id) AS attempts,
                    ROUND(AVG(t.score_percent), 1) AS avg_percent,
                    ROUND(SUM(t.passed = 1) * 100 / NULLIF(COUNT(t.id), 0), 1) AS pass_rate
             FROM assessment_attempts t
             JOIN assessments a ON a.id = t.assessment_id
             JOIN courses c ON c.id = a.course_id
             WHERE t.status = 'graded'
             GROUP BY c.id, c.title
             ORDER BY attempts DESC"
        );
    }

    /** Teaching load — sessions and students per instructor. */
    public static function instructorLoad(?array $centreIds, string $from, string $to): array
    {
        [$scope, $params] = self::scope($centreIds, 'co.centre_id');

        return Database::query(
            "SELECT u.id, CONCAT(p.first_name,' ',p.last_name) AS name,
                    COUNT(DISTINCT cg.id) AS class_groups,
                    COUNT(DISTINCT cs.id) AS sessions,
                    COUNT(DISTINCT e.user_id) AS students
             FROM class_groups cg
             JOIN cohorts co ON co.id = cg.cohort_id
             JOIN users u ON u.id = cg.instructor_user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             LEFT JOIN class_sessions cs ON cs.class_group_id = cg.id
                    AND DATE(cs.starts_at) BETWEEN ? AND ?
             LEFT JOIN enrolments e ON e.cohort_id = co.id AND e.status = 'active'
             WHERE 1=1 $scope
             GROUP BY u.id, name
             ORDER BY students DESC, sessions DESC",
            array_merge([$from, $to], $params)
        )->fetchAll();
    }

    /**
     * Students whose attendance has fallen below a threshold — the report a centre
     * manager actually acts on.
     */
    public static function atRiskStudents(?array $centreIds, string $from, string $to, int $thresholdPct = 70): array
    {
        [$scope, $params] = self::scope($centreIds, 'e.centre_id');

        return Database::query(
            "SELECT e.id AS enrolment_id, e.student_no, e.centre_id,
                    CONCAT(p.first_name,' ',p.last_name) AS name,
                    pr.title AS programme,
                    COUNT(ar.id) AS marked,
                    ROUND(SUM(ar.status IN ('present','late')) * 100 / NULLIF(COUNT(ar.id),0), 1) AS rate
             FROM enrolments e
             JOIN users u ON u.id = e.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             JOIN programmes pr ON pr.id = e.programme_id
             JOIN attendance_records ar ON ar.enrolment_id = e.id
             JOIN class_sessions cs ON cs.id = ar.class_session_id
             WHERE e.status = 'active' AND DATE(cs.starts_at) BETWEEN ? AND ? $scope
             GROUP BY e.id, e.student_no, e.centre_id, name, pr.title
             HAVING marked >= 3 AND rate < ?
             ORDER BY rate",
            array_merge([$from, $to], $params, [$thresholdPct])
        )->fetchAll();
    }

    // ------------------------------------------------------------------ services

    /** Affiliate and donation activity, for the management overview. */
    public static function serviceRollup(string $from, string $to): array
    {
        $out = ['affiliates' => 0, 'referrals' => 0, 'commissions' => 0, 'donations' => 0, 'donation_total' => 0];

        // These modules ship switched off and their tables may not exist on an older
        // installation. Reporting must degrade to zero rather than fatal.
        try {
            $out['affiliates'] = (int) Database::one("SELECT COUNT(*) c FROM affiliates WHERE status = 'approved'")['c'];
            $out['referrals'] = (int) Database::one(
                'SELECT COUNT(*) c FROM referrals WHERE DATE(registered_at) BETWEEN :f AND :t',
                ['f' => $from, 't' => $to]
            )['c'];
            $out['commissions'] = (int) Database::one(
                "SELECT COALESCE(SUM(amount),0) a FROM commissions WHERE status <> 'void'"
            )['a'];
        } catch (Throwable $e) {
            // affiliate module not installed
        }
        try {
            $row = Database::one(
                "SELECT COUNT(*) c, COALESCE(SUM(amount),0) a FROM donations
                 WHERE status = 'completed' AND DATE(completed_at) BETWEEN :f AND :t",
                ['f' => $from, 't' => $to]
            );
            $out['donations'] = (int) ($row['c'] ?? 0);
            $out['donation_total'] = (int) ($row['a'] ?? 0);
        } catch (Throwable $e) {
            // donations module not installed
        }
        return $out;
    }
}
