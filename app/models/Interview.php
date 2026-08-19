<?php
declare(strict_types=1);

/** Interview scheduling (brief §22). Panel membership lives in interview_panelists; feedback is INTERNAL ONLY (§23). */
final class Interview
{
    public const TYPES = ['physical' => 'Physical', 'online' => 'Online', 'telephone' => 'Telephone'];
    public const STATUSES = ['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rescheduled' => 'Rescheduled'];

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM interviews WHERE id = :id', ['id' => $id]);
    }

    public static function forApplication(int $jobApplicationId): array
    {
        return Database::all('SELECT * FROM interviews WHERE job_application_id = :a ORDER BY scheduled_at DESC', ['a' => $jobApplicationId]);
    }

    /** Interviews this user is on the panel for — the ownership scope for InterviewController::feedbackStore(). */
    public static function forPanelist(int $userId): array
    {
        return Database::all(
            "SELECT i.*, ja.reference, jp.title AS job_title
             FROM interviews i
             JOIN interview_panelists ip ON ip.interview_id = i.id
             JOIN job_applications ja ON ja.id = i.job_application_id
             JOIN job_postings jp ON jp.id = ja.job_posting_id
             WHERE ip.user_id = :u AND i.status IN ('scheduled','rescheduled')
             ORDER BY i.scheduled_at",
            ['u' => $userId]
        );
    }

    public static function isPanelist(int $interviewId, int $userId): bool
    {
        return Database::one(
            'SELECT 1 FROM interview_panelists WHERE interview_id = :i AND user_id = :u',
            ['i' => $interviewId, 'u' => $userId]
        ) !== null;
    }

    public static function panelistsFor(int $interviewId): array
    {
        return Database::all(
            "SELECT ip.*, CONCAT(pr.first_name, ' ', pr.last_name) AS name
             FROM interview_panelists ip JOIN user_profiles pr ON pr.user_id = ip.user_id
             WHERE ip.interview_id = :i",
            ['i' => $interviewId]
        );
    }

    /** @param array<int,int> $panelistUserIds */
    public static function create(
        int $jobApplicationId, ?string $scheduledAt, string $type, ?string $location,
        ?string $meetingLink, ?string $instructions, array $panelistUserIds
    ): int {
        Database::query(
            'INSERT INTO interviews (job_application_id, scheduled_at, type, location, meeting_link, instructions, created_by)
             VALUES (:a,:s,:t,:l,:m,:i,:by)',
            [
                'a' => $jobApplicationId, 's' => $scheduledAt ?: null, 't' => $type, 'l' => $location ?: null,
                'm' => $meetingLink ?: null, 'i' => $instructions ?: null, 'by' => Auth::id(),
            ]
        );
        $id = Database::lastInsertId();
        foreach (array_unique($panelistUserIds) as $userId) {
            Database::query('INSERT IGNORE INTO interview_panelists (interview_id, user_id) VALUES (:i,:u)', ['i' => $id, 'u' => (int) $userId]);
        }
        return $id;
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE interviews SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }

    /** Changing the time keeps the interview in `scheduled` — it's still upcoming, just at a new time. */
    public static function reschedule(int $id, string $newScheduledAt): void
    {
        Database::query(
            "UPDATE interviews SET scheduled_at = :s, status = 'scheduled' WHERE id = :id",
            ['s' => $newScheduledAt, 'id' => $id]
        );
    }
}
