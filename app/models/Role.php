<?php
declare(strict_types=1);

final class Role
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return Database::all('SELECT * FROM roles ORDER BY name');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM roles WHERE id = :id', ['id' => $id]);
    }

    public static function findByCode(string $code): ?array
    {
        return Database::one('SELECT * FROM roles WHERE code = :c', ['c' => $code]);
    }

    /**
     * System-granted relationship roles (03-rbac.md §2): "student, applicant and
     * affiliate are granted automatically by the system when the corresponding record is
     * created, and revoked when it closes. They are never assigned by hand."
     *
     * Phase 4 deferred this because nothing created those records yet. Applications and
     * enrolments now do.
     */
    public static function grantByCode(int $userId, string $code, ?int $centreId = null): void
    {
        $role = self::findByCode($code);
        if (!$role) {
            throw new RuntimeException("Unknown role code: $code");
        }
        Database::query(
            'INSERT IGNORE INTO user_roles (user_id, role_id, centre_id, granted_by) VALUES (:u,:r,:c,:by)',
            ['u' => $userId, 'r' => $role['id'], 'c' => $centreId, 'by' => Auth::id()]
        );
        Entitlements::flush($userId);
    }

    public static function revokeByCode(int $userId, string $code): void
    {
        $role = self::findByCode($code);
        if (!$role) {
            return;
        }
        Database::query(
            'DELETE FROM user_roles WHERE user_id = :u AND role_id = :r',
            ['u' => $userId, 'r' => $role['id']]
        );
        Entitlements::flush($userId);
    }

    public static function userHas(int $userId, string $code): bool
    {
        return Database::one(
            'SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :u AND r.code = :c LIMIT 1',
            ['u' => $userId, 'c' => $code]
        ) !== null;
    }
}
