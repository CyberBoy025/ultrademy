<?php
declare(strict_types=1);

final class ClassGroup
{
    public static function forCohort(int $cohortId): array
    {
        return Database::all(
            'SELECT cg.*, CONCAT(p.first_name, " ", p.last_name) AS instructor_name
             FROM class_groups cg LEFT JOIN users u ON u.id = cg.instructor_user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE cg.cohort_id = :c ORDER BY cg.name',
            ['c' => $cohortId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT cg.*, co.name AS cohort_name, co.id AS cohort_id, CONCAT(p.first_name, " ", p.last_name) AS instructor_name
             FROM class_groups cg JOIN cohorts co ON co.id = cg.cohort_id
             LEFT JOIN users u ON u.id = cg.instructor_user_id LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE cg.id = :id',
            ['id' => $id]
        );
    }

    /** Class groups an instructor teaches. */
    public static function forInstructor(int $userId): array
    {
        return Database::all(
            'SELECT cg.*, co.name AS cohort_name FROM class_groups cg JOIN cohorts co ON co.id = cg.cohort_id
             WHERE cg.instructor_user_id = :u ORDER BY cg.name',
            ['u' => $userId]
        );
    }

    public static function create(int $cohortId, ?int $instructorId, string $name, ?int $capacity): int
    {
        Database::query(
            'INSERT INTO class_groups (cohort_id, instructor_user_id, name, capacity) VALUES (:c,:i,:n,:cap)',
            ['c' => $cohortId, 'i' => $instructorId, 'n' => $name, 'cap' => $capacity]
        );
        return Database::lastInsertId();
    }
}
