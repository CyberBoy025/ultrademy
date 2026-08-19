<?php
declare(strict_types=1);

/** INTERNAL ONLY (brief §23) — never queried by any applicant-facing controller. */
final class InterviewFeedback
{
    public const RECOMMENDATIONS = ['strong_yes' => 'Strong Yes', 'yes' => 'Yes', 'no' => 'No', 'strong_no' => 'Strong No'];

    public static function forInterview(int $interviewId): array
    {
        return Database::all(
            "SELECT f.*, CONCAT(pr.first_name, ' ', pr.last_name) AS panelist_name
             FROM interview_feedback f JOIN user_profiles pr ON pr.user_id = f.panelist_user_id
             WHERE f.interview_id = :i ORDER BY f.created_at",
            ['i' => $interviewId]
        );
    }

    public static function submit(int $interviewId, int $panelistUserId, ?int $score, string $evaluation, string $strengths, string $concerns, ?string $recommendation): void
    {
        Database::query(
            'INSERT INTO interview_feedback (interview_id, panelist_user_id, score, evaluation, strengths, concerns, recommendation)
             VALUES (:i,:p,:s,:e,:st,:c,:r)
             ON DUPLICATE KEY UPDATE score = VALUES(score), evaluation = VALUES(evaluation),
                strengths = VALUES(strengths), concerns = VALUES(concerns), recommendation = VALUES(recommendation)',
            [
                'i' => $interviewId, 'p' => $panelistUserId, 's' => $score, 'e' => $evaluation ?: null,
                'st' => $strengths ?: null, 'c' => $concerns ?: null,
                'r' => isset(self::RECOMMENDATIONS[$recommendation]) ? $recommendation : null,
            ]
        );
    }
}
