<?php
declare(strict_types=1);

/**
 * Résumé-shaped data with no home on the shared `user_profiles` row (16-careers-portal.md
 * §5). One row per user, created on first touch of the wizard.
 */
final class JobApplicantProfile
{
    private const SUBDIR = 'recruitment';
    private const MAX_RESUME_BYTES = 5 * 1024 * 1024; // 5 MB

    public static function find(int $userId): ?array
    {
        return Database::one('SELECT * FROM job_applicant_profiles WHERE user_id = :u', ['u' => $userId]);
    }

    /** Always returns a row, creating an empty one on first touch. */
    public static function forUser(int $userId): array
    {
        $row = self::find($userId);
        if ($row !== null) {
            return $row;
        }
        Database::query('INSERT IGNORE INTO job_applicant_profiles (user_id) VALUES (:u)', ['u' => $userId]);
        return self::find($userId) ?? [
            'user_id' => $userId, 'professional_summary' => null, 'current_occupation' => null,
            'years_experience' => null, 'career_interests' => null, 'resume_stored_name' => null,
            'resume_original_name' => null, 'completion_pct' => 0,
        ];
    }

    public static function updateSummary(int $userId, string $summary, string $occupation, ?int $years, string $interests): void
    {
        self::forUser($userId);
        Database::query(
            'UPDATE job_applicant_profiles SET professional_summary = :s, current_occupation = :o,
                    years_experience = :y, career_interests = :i WHERE user_id = :u',
            ['s' => $summary ?: null, 'o' => $occupation ?: null, 'y' => $years, 'i' => $interests ?: null, 'u' => $userId]
        );
        self::recalculateCompletion($userId);
    }

    /** @param array $file one entry from $_FILES @return string|null error message, or null on success */
    public static function uploadResume(int $userId, array $file): ?string
    {
        $result = Upload::store($file, self::SUBDIR, ['pdf' => Upload::DOCUMENT_TYPES['pdf'], 'doc' => ['application/msword'], 'docx' => Upload::MATERIAL_TYPES['docx']], self::MAX_RESUME_BYTES);
        if (is_string($result)) {
            return $result;
        }
        self::forUser($userId);
        $old = self::find($userId);
        Database::query(
            'UPDATE job_applicant_profiles SET resume_original_name = :orig, resume_stored_name = :stored,
                    resume_mime_type = :mime, resume_size_bytes = :size WHERE user_id = :u',
            [
                'orig' => $result['original_name'], 'stored' => $result['stored_name'],
                'mime' => $result['mime_type'], 'size' => $result['size_bytes'], 'u' => $userId,
            ]
        );
        if ($old && $old['resume_stored_name']) {
            Upload::delete(self::SUBDIR, $old['resume_stored_name']);
        }
        self::recalculateCompletion($userId);
        return null;
    }

    public static function resumeStorageDir(): string
    {
        return Upload::dir(self::SUBDIR);
    }

    /**
     * Seven equally-weighted sections (brief §12's completion bar). Certifications are
     * intentionally excluded — the brief marks them "where applicable", not required.
     */
    public static function recalculateCompletion(int $userId): int
    {
        $profile = self::find($userId) ?? [];
        $user = Database::one('SELECT phone FROM users WHERE id = :u', ['u' => $userId]);
        $person = Database::one('SELECT address_line, city, state FROM user_profiles WHERE user_id = :u', ['u' => $userId]);

        $checks = [
            !empty($user['phone']) && !empty($person['address_line']) && !empty($person['city']) && !empty($person['state']),
            !empty($profile['professional_summary']),
            (int) (Database::one('SELECT COUNT(*) c FROM applicant_education WHERE user_id = :u', ['u' => $userId])['c'] ?? 0) > 0,
            (int) (Database::one('SELECT COUNT(*) c FROM applicant_experience WHERE user_id = :u', ['u' => $userId])['c'] ?? 0) > 0,
            (int) (Database::one('SELECT COUNT(*) c FROM applicant_skills WHERE user_id = :u', ['u' => $userId])['c'] ?? 0) > 0,
            !empty($profile['resume_stored_name']),
            (int) (Database::one('SELECT COUNT(*) c FROM applicant_references WHERE user_id = :u', ['u' => $userId])['c'] ?? 0) > 0,
        ];
        $pct = (int) round((count(array_filter($checks)) / count($checks)) * 100);

        self::forUser($userId);
        Database::query('UPDATE job_applicant_profiles SET completion_pct = :p WHERE user_id = :u', ['p' => $pct, 'u' => $userId]);
        return $pct;
    }
}
