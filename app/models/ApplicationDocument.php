<?php
declare(strict_types=1);

/**
 * Uploaded supporting documents (README §11 "upload required documents").
 *
 * These are identity documents and certificates — PII. Two deliberate choices:
 *
 * 1. Files are stored in `storage/app/documents/`, OUTSIDE the web root, so there is no
 *    URL that serves them directly. They are streamed by a controller that runs an
 *    authorisation check first. Putting ID scans in `public/uploads/` would make them
 *    fetchable by anyone who guesses a filename, which README §42 forbids.
 * 2. The stored filename is random and never derived from the user's filename, so a
 *    malicious name cannot traverse directories or end in an executable extension.
 */
final class ApplicationDocument
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    private const SUBDIR = 'documents';

    public static function storageDir(): string
    {
        return Upload::dir(self::SUBDIR);
    }

    public static function forApplication(int $applicationId): array
    {
        return Database::all(
            'SELECT * FROM application_documents WHERE application_id = :a ORDER BY uploaded_at',
            ['a' => $applicationId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM application_documents WHERE id = :id', ['id' => $id]);
    }

    /**
     * @param array $file one entry from $_FILES
     * @return string|null error message, or null on success
     */
    public static function store(int $applicationId, string $type, array $file): ?string
    {
        $result = Upload::store($file, self::SUBDIR, Upload::DOCUMENT_TYPES, self::MAX_BYTES);
        if (is_string($result)) {
            return $result;
        }

        Database::query(
            'INSERT INTO application_documents (application_id, type, original_name, stored_name, mime_type, size_bytes)
             VALUES (:a,:t,:orig,:stored,:mime,:size)',
            [
                'a' => $applicationId, 't' => $type,
                'orig' => $result['original_name'], 'stored' => $result['stored_name'],
                'mime' => $result['mime_type'], 'size' => $result['size_bytes'],
            ]
        );
        return null;
    }

    public static function setStatus(int $id, string $status, ?string $note): void
    {
        Database::query(
            'UPDATE application_documents SET status = :s, reviewer_note = :n WHERE id = :id',
            ['s' => $status, 'n' => $note, 'id' => $id]
        );
    }

    public static function delete(int $id): void
    {
        $doc = self::find($id);
        if (!$doc) {
            return;
        }
        Upload::delete(self::SUBDIR, $doc['stored_name']);
        Database::query('DELETE FROM application_documents WHERE id = :id', ['id' => $id]);
    }

    public static function humanSize(int $bytes): string
    {
        return Upload::humanSize($bytes);
    }
}
