<?php
declare(strict_types=1);

/**
 * Richer than the generic `notifications` email-queue row (brief §26: recipient, subject,
 * template, trigger, delivery status, error, related application). Status starts and stays
 * `queued` until a mail transport is actually configured — see notifications-cron.php's
 * own comment on the same platform-wide gap. Nothing here pretends a send happened.
 */
final class RecruitmentEmailLog
{
    public static function record(?int $jobApplicationId, string $recipientEmail, string $subject, string $templateCode, string $triggerType): int
    {
        Database::query(
            'INSERT INTO recruitment_email_logs (job_application_id, recipient_email, subject, template_code, trigger_type, status)
             VALUES (:a,:r,:s,:t,:tr,\'queued\')',
            ['a' => $jobApplicationId, 'r' => $recipientEmail, 's' => $subject, 't' => $templateCode, 'tr' => $triggerType]
        );
        return Database::lastInsertId();
    }

    /** @param array<string,string> $filters status, trigger_type */
    public static function search(array $filters = [], int $limit = 100): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['job_application_id'])) {
            $where[] = 'job_application_id = ?';
            $params[] = (int) $filters['job_application_id'];
        }
        $sql = 'SELECT l.*, ja.reference FROM recruitment_email_logs l LEFT JOIN job_applications ja ON ja.id = l.job_application_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $limit = max(1, min(500, $limit));
        $sql .= " ORDER BY l.created_at DESC LIMIT $limit";
        return Database::query($sql, $params)->fetchAll();
    }
}
