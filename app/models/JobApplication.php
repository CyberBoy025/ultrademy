<?php
declare(strict_types=1);

/**
 * Job applications (docs/architecture/16-careers-portal.md §5, §9). Named `job_applications`
 * — not `applications` — and grants a `job_applicant` role — not `applicant` — deliberately:
 * this codebase already uses both names for programme admissions. See migration 069.
 *
 * `job_posting_id + user_id` is unique (one application per posting per applicant, ever —
 * a withdrawn application cannot be resubmitted for the same posting in v1).
 */
final class JobApplication
{
    private const BASE_SELECT = "SELECT ja.*,
            jp.title AS job_title, jp.slug AS job_slug, jp.status AS job_status,
            u.email,
            CONCAT(pr.first_name, ' ', pr.last_name) AS applicant_name,
            pr.first_name, pr.last_name, u.phone
        FROM job_applications ja
        JOIN job_postings jp ON jp.id = ja.job_posting_id
        JOIN users u ON u.id = ja.user_id
        LEFT JOIN user_profiles pr ON pr.user_id = u.id";

    /** Statuses that still count as "open" for role revocation — draft is excluded, since the role is granted at submission, not at draft creation. */
    public const OPEN_STATUSES = ['submitted', 'received', 'under_review', 'shortlisted', 'interview', 'assessment', 'final_review'];

    /** Public-facing labels (brief §18/§20) — internal-only states never appear here. */
    public const STATUS_LABELS = [
        'draft' => 'Draft', 'submitted' => 'Submitted', 'received' => 'Received',
        'under_review' => 'Under Review', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview',
        'assessment' => 'Assessment', 'final_review' => 'Final Review', 'selected' => 'Selected',
        'rejected' => 'Not Successful', 'withdrawn' => 'Withdrawn', 'closed' => 'Closed',
    ];

    public static function find(int $id): ?array
    {
        return Database::one(self::BASE_SELECT . ' WHERE ja.id = ?', [$id]);
    }

    public static function findByReference(string $reference): ?array
    {
        return Database::one(self::BASE_SELECT . ' WHERE ja.reference = ?', [$reference]);
    }

    public static function findDraft(int $userId, int $jobPostingId): ?array
    {
        return Database::one(self::BASE_SELECT . ' WHERE ja.user_id = ? AND ja.job_posting_id = ?', [$userId, $jobPostingId]);
    }

    public static function forUser(int $userId): array
    {
        return Database::query(self::BASE_SELECT . " WHERE ja.user_id = ? AND ja.status <> 'draft' ORDER BY ja.submitted_at DESC", [$userId])->fetchAll();
    }

    /** Gets the existing application for this user+posting, or creates a fresh draft. */
    public static function getOrCreateDraft(int $userId, int $jobPostingId): array
    {
        $existing = self::findDraft($userId, $jobPostingId);
        if ($existing !== null) {
            return $existing;
        }
        Database::query(
            "INSERT INTO job_applications (reference, job_posting_id, user_id, status) VALUES (:ref,:p,:u,'draft')",
            ['ref' => self::nextReference(), 'p' => $jobPostingId, 'u' => $userId]
        );
        return self::find(Database::lastInsertId());
    }

    public static function updateCoverLetter(int $id, string $coverLetter): void
    {
        Database::query('UPDATE job_applications SET cover_letter = :c WHERE id = :id', ['c' => $coverLetter ?: null, 'id' => $id]);
    }

    /** Declaration + submission happen together — brief §47 records both at once. */
    public static function submit(int $id): void
    {
        Database::query(
            "UPDATE job_applications SET status = 'submitted', submitted_at = NOW(), declaration_accepted_at = NOW()
             WHERE id = :id AND status = 'draft'",
            ['id' => $id]
        );
    }

    public static function withdraw(int $id): void
    {
        Database::query("UPDATE job_applications SET status = 'withdrawn' WHERE id = :id", ['id' => $id]);
    }

    /**
     * Drops the `job_applicant` role once nothing is open any more.
     *
     * Deliberately diverges from Application::syncApplicantRole()'s precedent: admissions
     * keeps the `applicant` role through `approved` and only drops it at enrolment, because
     * enrolment is the event that hands the person a new relationship (`student`).
     * Recruitment has no such follow-on event yet — staff onboarding is explicitly future
     * work (brief §54) — so `selected` is treated as terminal here too, or the role would
     * simply never be cleared. Revisit this once onboarding exists.
     */
    public static function syncApplicantRole(int $userId): void
    {
        $ph = implode(',', array_fill(0, count(self::OPEN_STATUSES), '?'));
        $openCount = (int) Database::query(
            "SELECT COUNT(*) c FROM job_applications WHERE user_id = ? AND status IN ($ph)",
            array_merge([$userId], self::OPEN_STATUSES)
        )->fetch()['c'];
        if ($openCount === 0) {
            Role::revokeByCode($userId, 'job_applicant');
        }
    }

    // -------------------------------------------------------- staff / recruitment.application.*

    /** Statuses a recruiter moves an application through before a final decision (mirrors admissions' review/decide split). */
    public const REVIEW_STATUSES = ['received', 'under_review', 'shortlisted', 'interview', 'assessment', 'final_review'];
    /** Terminal outcomes — gated by recruitment.application.decide, never .review. */
    public const DECISION_STATUSES = ['selected', 'rejected'];

    /**
     * Pipeline listing for recruiters/admins — every non-draft application, optionally
     * filtered.
     *
     * @param array<string,mixed> $filters job_posting_id, status
     * @param array<int,int>|null $centreIds from Auth::scopeCentres() — null = global,
     *     [] = nothing visible, [ids] = only postings tied to one of these centres
     *     (a recruiter scoped to a centre does not see remote/HQ postings, mirroring
     *     admissions' Decision 8 default for centre_manager).
     */
    public static function pipeline(array $filters = [], ?array $centreIds = null): array
    {
        if ($centreIds !== null && empty($centreIds)) {
            return [];
        }
        $where = ["ja.status <> 'draft'"];
        $params = [];
        if (!empty($filters['job_posting_id'])) {
            $where[] = 'ja.job_posting_id = ?';
            $params[] = (int) $filters['job_posting_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ja.status = ?';
            $params[] = $filters['status'];
        }
        if ($centreIds !== null) {
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $where[] = "ja.job_posting_id IN (SELECT job_posting_id FROM job_posting_centres WHERE centre_id IN ($ph))";
            array_push($params, ...array_values($centreIds));
        }
        $sql = self::BASE_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ja.submitted_at DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    /** @param array<string,mixed> $filters @param array<int,int>|null $centreIds */
    public static function pipelineCounts(array $filters = [], ?array $centreIds = null): array
    {
        $counts = [];
        foreach (self::pipeline($filters, $centreIds) as $row) {
            $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
        }
        return $counts;
    }

    public static function setStatus(int $id, string $status, ?int $reviewedBy, ?string $note = null): void
    {
        Database::query(
            'UPDATE job_applications SET status = :s, reviewed_by = :by, reviewed_at = NOW(), decision_note = COALESCE(:n, decision_note) WHERE id = :id',
            ['s' => $status, 'by' => $reviewedBy, 'n' => $note, 'id' => $id]
        );
    }

    /**
     * Builds the {{token}} => value set EmailTemplate::render() substitutes (brief §25).
     * @param array<string,mixed> $app a row from find()/pipeline()/etc
     * @param array<string,mixed>|null $interview a row from Interview::find(), if the event is interview-related
     * @return array<string,string>
     */
    public static function emailVars(array $app, ?array $interview = null): array
    {
        return [
            'applicant_name' => $app['applicant_name'] ?: $app['email'],
            'job_title' => (string) $app['job_title'],
            'application_number' => (string) $app['reference'],
            'application_status' => self::STATUS_LABELS[$app['status']] ?? $app['status'],
            'decision_note' => (string) ($app['decision_note'] ?? ''),
            'interview_date' => $interview && $interview['scheduled_at'] ? date('l, F j, Y', strtotime($interview['scheduled_at'])) : '',
            'interview_time' => $interview && $interview['scheduled_at'] ? date('g:ia', strtotime($interview['scheduled_at'])) : '',
            'company_name' => (string) (Setting::get('site_name') ?? 'UltrAdemy'),
        ];
    }

    private static function nextReference(): string
    {
        // UTD-JA-2026-000001 — brief §15's exact format.
        $year = date('Y');
        $row = Database::one("SELECT COUNT(*) c FROM job_applications WHERE reference LIKE :like", ['like' => "UTD-JA-$year-%"]);
        return sprintf('UTD-JA-%s-%06d', $year, ((int) $row['c']) + 1);
    }
}
