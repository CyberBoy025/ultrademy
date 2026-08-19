<?php
declare(strict_types=1);

final class ApplicantReference
{
    public static function forUser(int $userId): array
    {
        return Database::all('SELECT * FROM applicant_references WHERE user_id = :u ORDER BY sort_order, id', ['u' => $userId]);
    }

    public static function create(int $userId, string $name, string $relationship, string $organisation, string $email, string $phone): int
    {
        Database::query(
            'INSERT INTO applicant_references (user_id, name, relationship, organisation, email, phone) VALUES (:u,:n,:r,:o,:e,:p)',
            ['u' => $userId, 'n' => $name, 'r' => $relationship ?: null, 'o' => $organisation ?: null, 'e' => $email ?: null, 'p' => $phone ?: null]
        );
        return Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        Database::query('DELETE FROM applicant_references WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $userId]);
    }
}
