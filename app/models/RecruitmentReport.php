<?php
declare(strict_types=1);

/**
 * Recruitment reporting (brief §33). Mirrors FinanceReport's shape: a date range plus an
 * optional centre scope (from Auth::scopeCentres('recruitment.report.view')) — null means
 * global, an array restricts to postings tied to those centres, exactly like the
 * applicant pipeline already does in JobApplication::pipeline().
 */
final class RecruitmentReport
{
    /** @param array<int,int>|null $centreIds */
    private static function centreClause(?array $centreIds, string $column = 'ja.job_posting_id'): array
    {
        if ($centreIds === null) {
            return ['', []];
        }
        if (empty($centreIds)) {
            return [' AND 1=0', []];
        }
        $ph = implode(',', array_fill(0, count($centreIds), '?'));
        return [" AND $column IN (SELECT job_posting_id FROM job_posting_centres WHERE centre_id IN ($ph))", $centreIds];
    }

    public static function vacancySummary(): array
    {
        $rows = Database::all("SELECT status, COUNT(*) c FROM job_postings GROUP BY status");
        $out = ['total' => 0, 'draft' => 0, 'published' => 0, 'unpublished' => 0, 'closed' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['total'] += (int) $r['c'];
        }
        return $out;
    }

    /** @param array<int,int>|null $centreIds */
    public static function applicationSummary(string $from, string $to, ?array $centreIds): array
    {
        [$clause, $params] = self::centreClause($centreIds);
        $sql = "SELECT status, COUNT(*) c FROM job_applications ja
                WHERE ja.status <> 'draft' AND ja.submitted_at BETWEEN ? AND ?" . $clause . " GROUP BY status";
        $rows = Database::query($sql, [...[$from, $to . ' 23:59:59'], ...$params])->fetchAll();

        $counts = [];
        $total = 0;
        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['c'];
            $total += (int) $r['c'];
        }
        $selected = $counts['selected'] ?? 0;
        $rejected = $counts['rejected'] ?? 0;
        $withdrawn = $counts['withdrawn'] ?? 0;
        $inReview = 0;
        foreach (JobApplication::REVIEW_STATUSES as $s) {
            $inReview += $counts[$s] ?? 0;
        }
        return [
            'total' => $total, 'in_review' => $inReview, 'selected' => $selected,
            'rejected' => $rejected, 'withdrawn' => $withdrawn,
            'conversion_pct' => $total > 0 ? round($selected / $total * 100, 1) : 0.0,
            'by_status' => $counts,
        ];
    }

    /** @param array<int,int>|null $centreIds */
    public static function byJob(string $from, string $to, ?array $centreIds): array
    {
        [$clause, $params] = self::centreClause($centreIds);
        $sql = "SELECT jp.id, jp.title, COUNT(ja.id) c
                FROM job_applications ja JOIN job_postings jp ON jp.id = ja.job_posting_id
                WHERE ja.status <> 'draft' AND ja.submitted_at BETWEEN ? AND ?" . $clause . "
                GROUP BY jp.id, jp.title ORDER BY c DESC";
        return Database::query($sql, [...[$from, $to . ' 23:59:59'], ...$params])->fetchAll();
    }

    /** Global only — mirrors FinanceReport::byCentre() being shown only when scope is global. */
    public static function byCentre(string $from, string $to): array
    {
        return Database::all(
            "SELECT c.name, COUNT(DISTINCT ja.id) c
             FROM job_applications ja
             JOIN job_posting_centres jpc ON jpc.job_posting_id = ja.job_posting_id
             JOIN centres c ON c.id = jpc.centre_id
             WHERE ja.status <> 'draft' AND ja.submitted_at BETWEEN ? AND ?
             GROUP BY c.id, c.name ORDER BY c DESC",
            [$from, $to . ' 23:59:59']
        );
    }

    /** @param array<int,int>|null $centreIds */
    public static function interviewStats(string $from, string $to, ?array $centreIds): array
    {
        [$clause, $params] = self::centreClause($centreIds, 'ja.job_posting_id');
        $sql = "SELECT i.status, COUNT(*) c FROM interviews i
                JOIN job_applications ja ON ja.id = i.job_application_id
                WHERE i.created_at BETWEEN ? AND ?" . $clause . " GROUP BY i.status";
        $rows = Database::query($sql, [...[$from, $to . ' 23:59:59'], ...$params])->fetchAll();
        $out = ['scheduled' => 0, 'completed' => 0, 'cancelled' => 0, 'rescheduled' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
        }
        return $out;
    }

    /** @param array<int,int>|null $centreIds @return array<int,array<string,mixed>> recent status changes, newest first */
    public static function recentActivity(?array $centreIds, int $limit = 10): array
    {
        [$clause, $params] = self::centreClause($centreIds, 'ja.job_posting_id');
        $limit = max(1, min(50, $limit));
        $sql = "SELECT h.*, ja.reference, jp.title AS job_title
                FROM job_application_status_history h
                JOIN job_applications ja ON ja.id = h.job_application_id
                JOIN job_postings jp ON jp.id = ja.job_posting_id
                WHERE 1=1" . $clause . "
                ORDER BY h.created_at DESC LIMIT $limit";
        return Database::query($sql, $params)->fetchAll();
    }
}
