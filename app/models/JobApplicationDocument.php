<?php
declare(strict_types=1);

/** Mirrors ApplicationDocument.php exactly — outside the web root, random filenames, streamed only after an authorisation check. */
final class JobApplicationDocument
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const SUBDIR = 'recruitment';

    public const TYPES = [
        'cv' => 'CV / Résumé', 'cover_letter' => 'Cover Letter', 'certificate' => 'Certificate',
        'credential' => 'Credential', 'portfolio' => 'Portfolio', 'identification' => 'Identification', 'other' => 'Other',
    ];

    public static function storageDir(): string
    {
        return Upload::dir(self::SUBDIR);
    }

    public static function forApplication(int $jobApplicationId): array
    {
        return Database::all('SELECT * FROM job_application_documents WHERE job_application_id = :a ORDER BY uploaded_at', ['a' => $jobApplicationId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM job_application_documents WHERE id = :id', ['id' => $id]);
    }

    /** @param array $file one entry from $_FILES @return string|null error message, or null on success */
    public static function store(int $jobApplicationId, string $type, array $file): ?string
    {
        $result = Upload::store($file, self::SUBDIR, Upload::DOCUMENT_TYPES, self::MAX_BYTES);
        if (is_string($result)) {
            return $result;
        }
        Database::query(
            'INSERT INTO job_application_documents (job_application_id, type, original_name, stored_name, mime_type, size_bytes)
             VALUES (:a,:t,:orig,:stored,:mime,:size)',
            [
                'a' => $jobApplicationId, 't' => isset(self::TYPES[$type]) ? $type : 'other',
                'orig' => $result['original_name'], 'stored' => $result['stored_name'],
                'mime' => $result['mime_type'], 'size' => $result['size_bytes'],
            ]
        );
        return null;
    }

    public static function delete(int $id): void
    {
        $doc = self::find($id);
        if (!$doc) {
            return;
        }
        Upload::delete(self::SUBDIR, $doc['stored_name']);
        Database::query('DELETE FROM job_application_documents WHERE id = :id', ['id' => $id]);
    }
}
