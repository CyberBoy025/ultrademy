<?php
declare(strict_types=1);

/** Per-user grants and revocations that sit on top of whatever the package says (§5 step 3). */
final class EntitlementOverride
{
    public static function forUser(int $userId): array
    {
        return Database::all(
            "SELECT o.*, f.code AS feature_code, f.name AS feature_name,
                    CONCAT(pr.first_name,' ',pr.last_name) AS granted_by_name
             FROM entitlement_overrides o
             JOIN features f ON f.id = o.feature_id
             LEFT JOIN user_profiles pr ON pr.user_id = o.granted_by
             WHERE o.user_id = :u ORDER BY f.module, f.name",
            ['u' => $userId]
        );
    }

    public static function all(): array
    {
        return Database::all(
            "SELECT o.*, f.code AS feature_code, f.name AS feature_name, u.email,
                    CONCAT(pr.first_name,' ',pr.last_name) AS user_name
             FROM entitlement_overrides o
             JOIN features f ON f.id = o.feature_id
             JOIN users u ON u.id = o.user_id
             LEFT JOIN user_profiles pr ON pr.user_id = u.id
             ORDER BY o.created_at DESC"
        );
    }

    public static function set(int $userId, int $featureId, bool $granted, ?int $limit, ?string $expiresAt, string $reason): void
    {
        Database::query(
            'INSERT INTO entitlement_overrides (user_id, feature_id, granted, limit_value, expires_at, reason, granted_by)
             VALUES (:u,:f,:g,:l,:e,:r,:by)
             ON DUPLICATE KEY UPDATE granted=VALUES(granted), limit_value=VALUES(limit_value),
                                     expires_at=VALUES(expires_at), reason=VALUES(reason), granted_by=VALUES(granted_by)',
            [
                'u' => $userId, 'f' => $featureId, 'g' => $granted ? 1 : 0, 'l' => $limit,
                'e' => $expiresAt ?: null, 'r' => $reason, 'by' => Auth::id(),
            ]
        );
        Entitlements::flush($userId);
    }

    public static function remove(int $id): void
    {
        $row = Database::one('SELECT user_id FROM entitlement_overrides WHERE id = :id', ['id' => $id]);
        Database::query('DELETE FROM entitlement_overrides WHERE id = :id', ['id' => $id]);
        if ($row) {
            Entitlements::flush((int) $row['user_id']);
        }
    }
}
