<?php
declare(strict_types=1);

final class StaffCentre
{
    public static function allForCentre(int $centreId): array
    {
        return Database::all(
            "SELECT sc.*, u.email, p.first_name, p.last_name,
                    GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS role_names
             FROM staff_centres sc
             JOIN users u ON u.id = sc.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             LEFT JOIN user_roles ur ON ur.user_id = u.id AND (ur.centre_id = sc.centre_id OR ur.centre_id IS NULL)
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE sc.centre_id = :c
             GROUP BY sc.id
             ORDER BY p.first_name",
            ['c' => $centreId]
        );
    }

    /** @return array<int,array<string,mixed>> every posting, across centres — for the cross-centre Staff page */
    public static function allWithCentre(?array $centreIds = null): array
    {
        $sql = "SELECT sc.*, u.email, p.first_name, p.last_name, c.name AS centre_name,
                       GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS role_names
                FROM staff_centres sc
                JOIN users u ON u.id = sc.user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id
                JOIN centres c ON c.id = sc.centre_id
                LEFT JOIN user_roles ur ON ur.user_id = u.id AND (ur.centre_id = sc.centre_id OR ur.centre_id IS NULL)
                LEFT JOIN roles r ON r.id = ur.role_id";
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " WHERE sc.centre_id IN ($ph)";
            $params = array_values($centreIds);
        }
        $sql .= ' GROUP BY sc.id ORDER BY c.name, p.first_name';
        return Database::query($sql, $params)->fetchAll();
    }

    public static function assign(int $userId, int $centreId, bool $isPrimary = false): void
    {
        Database::query(
            'INSERT IGNORE INTO staff_centres (user_id, centre_id, is_primary) VALUES (:u,:c,:p)',
            ['u' => $userId, 'c' => $centreId, 'p' => $isPrimary ? 1 : 0]
        );
    }
}
