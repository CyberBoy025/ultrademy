<?php
declare(strict_types=1);

/** INTERNAL ONLY (brief §31) — never queried by any applicant-facing controller. */
final class RecruitmentNote
{
    public static function forApplication(int $jobApplicationId): array
    {
        return Database::all(
            "SELECT n.*, CONCAT(pr.first_name, ' ', pr.last_name) AS author_name
             FROM recruitment_notes n LEFT JOIN user_profiles pr ON pr.user_id = n.author_id
             WHERE n.job_application_id = :a ORDER BY n.created_at DESC",
            ['a' => $jobApplicationId]
        );
    }

    public static function create(int $jobApplicationId, int $authorId, string $note): int
    {
        Database::query(
            'INSERT INTO recruitment_notes (job_application_id, author_id, note) VALUES (:a,:by,:n)',
            ['a' => $jobApplicationId, 'by' => $authorId, 'n' => $note]
        );
        return Database::lastInsertId();
    }
}
