<?php
declare(strict_types=1);

final class ApplicationAnswer
{
    /** @return array<int,array<string,mixed>> answers joined to their question, in question sort order */
    public static function forApplication(int $jobApplicationId): array
    {
        return Database::all(
            'SELECT aa.*, jq.label, jq.type AS question_type
             FROM application_answers aa JOIN job_questions jq ON jq.id = aa.job_question_id
             WHERE aa.job_application_id = :a ORDER BY jq.sort_order',
            ['a' => $jobApplicationId]
        );
    }

    /** @param array<int,string> $answers job_question_id => text answer */
    public static function saveAnswers(int $jobApplicationId, array $answers): void
    {
        foreach ($answers as $questionId => $text) {
            Database::query(
                'INSERT INTO application_answers (job_application_id, job_question_id, answer_text)
                 VALUES (:a,:q,:t) ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text)',
                ['a' => $jobApplicationId, 'q' => (int) $questionId, 't' => trim((string) $text) ?: null]
            );
        }
    }
}
