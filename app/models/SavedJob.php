<?php
declare(strict_types=1);

final class SavedJob
{
    public static function idsForUser(int $userId): array
    {
        return array_map('intval', array_column(
            Database::all('SELECT job_posting_id FROM saved_jobs WHERE user_id = :u', ['u' => $userId]),
            'job_posting_id'
        ));
    }

    public static function save(int $userId, int $jobPostingId): void
    {
        Database::query(
            'INSERT IGNORE INTO saved_jobs (user_id, job_posting_id) VALUES (:u,:j)',
            ['u' => $userId, 'j' => $jobPostingId]
        );
    }

    public static function unsave(int $userId, int $jobPostingId): void
    {
        Database::query('DELETE FROM saved_jobs WHERE user_id = :u AND job_posting_id = :j', ['u' => $userId, 'j' => $jobPostingId]);
    }
}
