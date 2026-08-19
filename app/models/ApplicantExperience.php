<?php
declare(strict_types=1);

final class ApplicantExperience
{
    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT * FROM applicant_experience WHERE user_id = :u ORDER BY is_current DESC, start_date DESC',
            ['u' => $userId]
        );
    }

    public static function create(
        int $userId, string $organisation, string $jobTitle, ?string $employmentType,
        ?string $startDate, ?string $endDate, bool $isCurrent, string $responsibilities, string $reasonForLeaving
    ): int {
        Database::query(
            'INSERT INTO applicant_experience
                (user_id, organisation, job_title, employment_type, start_date, end_date, is_current, responsibilities, reason_for_leaving)
             VALUES (:u,:o,:t,:et,:sd,:ed,:cur,:resp,:reason)',
            [
                'u' => $userId, 'o' => $organisation, 't' => $jobTitle, 'et' => $employmentType ?: null,
                'sd' => $startDate ?: null, 'ed' => $isCurrent ? null : ($endDate ?: null), 'cur' => $isCurrent ? 1 : 0,
                'resp' => $responsibilities ?: null, 'reason' => $reasonForLeaving ?: null,
            ]
        );
        return Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        Database::query('DELETE FROM applicant_experience WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $userId]);
    }
}
