<?php
declare(strict_types=1);

final class Enrolment
{
    public static function forCohort(int $cohortId): array
    {
        return Database::all(
            'SELECT e.*, CONCAT(p.first_name, " ", p.last_name) AS student_name, u.email
             FROM enrolments e JOIN users u ON u.id = e.user_id LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE e.cohort_id = :c ORDER BY p.first_name',
            ['c' => $cohortId]
        );
    }

    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT e.*, p.title AS programme_title, co.name AS cohort_name FROM enrolments e
             JOIN programmes p ON p.id = e.programme_id JOIN cohorts co ON co.id = e.cohort_id
             WHERE e.user_id = :u ORDER BY e.enrolled_at DESC',
            ['u' => $userId]
        );
    }

    public static function count(): int
    {
        return (int) Database::one("SELECT COUNT(*) c FROM enrolments WHERE status='active'")['c'];
    }
}
