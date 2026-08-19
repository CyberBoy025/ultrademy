<?php
declare(strict_types=1);

final class AuditLog
{
    public static function recent(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return Database::query(
            "SELECT al.*, CONCAT(p.first_name, ' ', p.last_name) AS actor_name
             FROM audit_logs al LEFT JOIN users u ON u.id = al.actor_user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             ORDER BY al.created_at DESC LIMIT $limit"
        )->fetchAll();
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) c FROM audit_logs')['c'];
    }
}
