<?php
declare(strict_types=1);

final class ClassSession
{
    private const BASE_SELECT = "SELECT cs.*, cg.name AS group_name, co.name AS cohort_name, co.centre_id,
            c.name AS centre_name, r.name AS room_name, p.title AS programme_title,
            CONCAT(pr.first_name, ' ', pr.last_name) AS instructor_name
        FROM class_sessions cs
        JOIN class_groups cg ON cg.id = cs.class_group_id
        JOIN cohorts co ON co.id = cg.cohort_id
        JOIN programmes p ON p.id = co.programme_id
        LEFT JOIN centres c ON c.id = co.centre_id
        LEFT JOIN rooms r ON r.id = cs.room_id
        LEFT JOIN users u ON u.id = cg.instructor_user_id
        LEFT JOIN user_profiles pr ON pr.user_id = u.id";

    /** @param array<int,int>|null $centreIds null = global scope (no filter), [] = no access */
    public static function upcoming(?array $centreIds, int $daysAhead = 14): array
    {
        $where = 'WHERE cs.starts_at BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND DATE_ADD(NOW(), INTERVAL ? DAY)';
        $params = [$daysAhead];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $where .= " AND co.centre_id IN ($ph)";
            array_push($params, ...array_values($centreIds));
        }
        return Database::query(self::BASE_SELECT . " $where ORDER BY cs.starts_at", $params)->fetchAll();
    }

    public static function forGroup(int $groupId): array
    {
        return Database::query(self::BASE_SELECT . ' WHERE cs.class_group_id = ? ORDER BY cs.starts_at', [$groupId])->fetchAll();
    }

    /**
     * Sessions for the cohorts this user is actively enrolled in, plus (if they instruct)
     * the ones they teach. Ownership-scoped: a student can only ever see their own — there
     * is no query shape here that returns another student's schedule (03-rbac.md §7).
     */
    public static function forUser(int $userId, int $daysAhead = 30): array
    {
        return Database::query(
            self::BASE_SELECT . '
             WHERE cs.starts_at BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND DATE_ADD(NOW(), INTERVAL ? DAY)
               AND (
                    cg.cohort_id IN (SELECT cohort_id FROM enrolments WHERE user_id = ? AND status = "active")
                 OR cg.instructor_user_id = ?
               )
             ORDER BY cs.starts_at',
            [$daysAhead, $userId, $userId]
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE cs.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function create(int $groupId, ?int $roomId, string $topic, string $startsAt, string $endsAt, string $mode): int
    {
        Database::query(
            'INSERT INTO class_sessions (class_group_id, room_id, topic, starts_at, ends_at, mode) VALUES (:g,:r,:t,:s,:e,:m)',
            ['g' => $groupId, 'r' => $roomId, 't' => $topic, 's' => $startsAt, 'e' => $endsAt, 'm' => $mode]
        );
        return Database::lastInsertId();
    }
}
