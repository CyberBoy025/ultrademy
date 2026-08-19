<?php
declare(strict_types=1);

final class ApplicantEducation
{
    public static function forUser(int $userId): array
    {
        return Database::all('SELECT * FROM applicant_education WHERE user_id = :u ORDER BY sort_order, end_year DESC', ['u' => $userId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM applicant_education WHERE id = :id', ['id' => $id]);
    }

    public static function create(int $userId, string $institution, string $qualification, string $field, ?int $startYear, ?int $endYear): int
    {
        Database::query(
            'INSERT INTO applicant_education (user_id, institution, qualification, field_of_study, start_year, end_year)
             VALUES (:u,:i,:q,:f,:sy,:ey)',
            ['u' => $userId, 'i' => $institution, 'q' => $qualification, 'f' => $field ?: null, 'sy' => $startYear, 'ey' => $endYear]
        );
        return Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        Database::query('DELETE FROM applicant_education WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $userId]);
    }
}
