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

    /** Extension => allowed MIME types. Deliberately a short allow-list, not a deny-list. */
    private const ALLOWED = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    public static function storageDir(): string
    {
        return config('app.root') . '/storage/app/documents';
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
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is too large.',
                UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
                default => 'Upload failed. Please try again.',
            };
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Upload failed.';
        }
        if ($file['size'] > self::MAX_BYTES) {
            return 'Files must be 5 MB or smaller.';
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            return 'Only PDF, JPG and PNG files are accepted.';
        }

        // Trust the file's actual content, not the browser-supplied Content-Type.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED[$ext], true)) {
            return 'That file\'s contents do not match its extension.';
        }

        $dir = self::storageDir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return 'Storage is unavailable — contact an administrator.';
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$stored")) {
            return 'Could not save the file.';
        }

        Database::query(
            'INSERT INTO application_documents (application_id, type, original_name, stored_name, mime_type, size_bytes)
             VALUES (:a,:t,:orig,:stored,:mime,:size)',
            [
                'a' => $applicationId, 't' => $type,
                'orig' => mb_substr((string) $file['name'], 0, 255),
                'stored' => $stored, 'mime' => $mime, 'size' => (int) $file['size'],
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
        $path = self::storageDir() . '/' . $doc['stored_name'];
        if (is_file($path)) {
            unlink($path);
        }
        Database::query('DELETE FROM application_documents WHERE id = :id', ['id' => $id]);
    }

    public static function humanSize(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
