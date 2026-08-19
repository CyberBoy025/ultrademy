<?php
declare(strict_types=1);

/** Append-only transition log (brief §18) — mirrors the discipline Audit.php applies platform-wide. */
final class JobApplicationStatusHistory
{
    public static function record(int $jobApplicationId, ?string $from, string $to, ?int $changedBy, ?string $note = null): void
    {
        Database::query(
            'INSERT INTO job_application_status_history (job_application_id, from_status, to_status, changed_by, note)
             VALUES (:a,:f,:t,:by,:n)',
            ['a' => $jobApplicationId, 'f' => $from, 't' => $to, 'by' => $changedBy, 'n' => $note]
        );
    }

    public static function forApplication(int $jobApplicationId): array
    {
        return Database::all(
            'SELECT * FROM job_application_status_history WHERE job_application_id = :a ORDER BY created_at',
            ['a' => $jobApplicationId]
        );
    }
}
