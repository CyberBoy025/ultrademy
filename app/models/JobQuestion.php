<?php
declare(strict_types=1);

final class JobQuestion
{
    public const TYPES = [
        'short_text' => 'Short text', 'long_text' => 'Long text', 'yes_no' => 'Yes / No',
        'multiple_choice' => 'Multiple choice', 'date' => 'Date', 'number' => 'Number', 'file' => 'File upload',
    ];

    public static function forPosting(int $jobPostingId): array
    {
        return Database::all('SELECT * FROM job_questions WHERE job_posting_id = :p ORDER BY sort_order', ['p' => $jobPostingId]);
    }

    public static function create(int $jobPostingId, string $label, string $type, bool $isRequired, ?array $options = null): int
    {
        $nextOrder = (int) (Database::one('SELECT COALESCE(MAX(sort_order),-1)+1 n FROM job_questions WHERE job_posting_id = :p', ['p' => $jobPostingId])['n'] ?? 0);
        Database::query(
            'INSERT INTO job_questions (job_posting_id, label, type, options, is_required, sort_order) VALUES (:p,:l,:t,:o,:r,:s)',
            [
                'p' => $jobPostingId, 'l' => $label, 't' => isset(self::TYPES[$type]) ? $type : 'short_text',
                'o' => $options ? json_encode($options) : null, 'r' => $isRequired ? 1 : 0, 's' => $nextOrder,
            ]
        );
        return Database::lastInsertId();
    }

    public static function delete(int $id, int $jobPostingId): void
    {
        Database::query('DELETE FROM job_questions WHERE id = :id AND job_posting_id = :p', ['id' => $id, 'p' => $jobPostingId]);
    }
}
