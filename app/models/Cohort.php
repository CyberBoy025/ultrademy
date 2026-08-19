<?php
declare(strict_types=1);

final class Cohort
{
    /** @param array<int,int>|null $centreIds null = no restriction (global scope) */
    public static function all(?array $centreIds = null): array
    {
        $sql = 'SELECT co.*, p.title AS programme_title, c.name AS centre_name
                FROM cohorts co
                JOIN programmes p ON p.id = co.programme_id
                LEFT JOIN centres c ON c.id = co.centre_id';
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " WHERE co.centre_id IN ($ph)";
            $params = array_values($centreIds);
        }
        $sql .= ' ORDER BY co.starts_on DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    public static function forProgramme(int $programmeId): array
    {
        return Database::all(
            'SELECT co.*, c.name AS centre_name FROM cohorts co LEFT JOIN centres c ON c.id = co.centre_id
             WHERE co.programme_id = :p ORDER BY co.starts_on DESC',
            ['p' => $programmeId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT co.*, p.title AS programme_title, c.name AS centre_name
             FROM cohorts co JOIN programmes p ON p.id = co.programme_id LEFT JOIN centres c ON c.id = co.centre_id
             WHERE co.id = :id',
            ['id' => $id]
        );
    }

    public static function enrolledCount(int $cohortId): int
    {
        return (int) Database::one("SELECT COUNT(*) c FROM enrolments WHERE cohort_id=:id AND status='active'", ['id' => $cohortId])['c'];
    }

    public static function create(int $programmeId, ?int $centreId, string $code, string $name, ?string $startsOn, ?string $endsOn, ?int $capacity): int
    {
        Database::query(
            'INSERT INTO cohorts (programme_id, centre_id, code, name, starts_on, ends_on, capacity)
             VALUES (:p,:c,:code,:name,:s,:e,:cap)',
            ['p' => $programmeId, 'c' => $centreId, 'code' => $code, 'name' => $name, 's' => $startsOn, 'e' => $endsOn, 'cap' => $capacity]
        );
        return Database::lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE cohorts SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }
}
