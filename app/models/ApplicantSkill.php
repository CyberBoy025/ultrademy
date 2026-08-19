<?php
declare(strict_types=1);

final class ApplicantSkill
{
    public const TYPES = ['technical' => 'Technical', 'professional' => 'Professional', 'software' => 'Software / Tools', 'language' => 'Language'];
    public const PROFICIENCIES = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'];

    public static function forUser(int $userId): array
    {
        return Database::all('SELECT * FROM applicant_skills WHERE user_id = :u ORDER BY skill_type, skill_name', ['u' => $userId]);
    }

    public static function create(int $userId, string $name, string $type, ?string $proficiency): int
    {
        Database::query(
            'INSERT INTO applicant_skills (user_id, skill_name, skill_type, proficiency) VALUES (:u,:n,:t,:p)',
            ['u' => $userId, 'n' => $name, 't' => isset(self::TYPES[$type]) ? $type : 'technical', 'p' => $proficiency ?: null]
        );
        return Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        Database::query('DELETE FROM applicant_skills WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $userId]);
    }
}
