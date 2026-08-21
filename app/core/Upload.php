<?php
declare(strict_types=1);

/**
 * One place that decides what a safe upload is.
 *
 * Extracted in Phase 8 when a third caller appeared (application documents, lesson
 * materials, assignment submissions). Duplicating "which extensions are allowed" three
 * times is how one copy quietly ends up permitting .phtml.
 *
 * Everything lands under storage/app/<subdir>/ — outside any routable path — with a
 * random filename. Serving is always the caller's job, through a controller that
 * authorises first.
 */
final class Upload
{
    public const DOCUMENT_TYPES = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    /** A profile photo — image only, no PDF. */
    public const IMAGE_TYPES = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /** Course materials additionally allow common office/media formats. */
    public const MATERIAL_TYPES = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'mp4'  => ['video/mp4'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    public static function dir(string $subdir): string
    {
        return config('app.root') . '/storage/app/' . $subdir;
    }

    /**
     * @param array $file one entry from $_FILES
     * @param array<string,array<int,string>> $allowed extension => acceptable MIME types
     * @return array{stored_name:string,original_name:string,mime_type:string,size_bytes:int}|string
     *         the stored metadata, or an error message
     */
    public static function store(array $file, string $subdir, array $allowed, int $maxBytes)
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            return match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is too large.',
                UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
                default => 'Upload failed. Please try again.',
            };
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Upload failed.';
        }
        if ((int) $file['size'] > $maxBytes) {
            return 'Files must be ' . self::humanSize($maxBytes) . ' or smaller.';
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            return 'Allowed file types: ' . strtoupper(implode(', ', array_keys($allowed))) . '.';
        }

        // Trust the bytes, not the browser-supplied Content-Type.
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $allowed[$ext], true)) {
            return "That file's contents do not match its extension.";
        }

        $dir = self::dir($subdir);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return 'Storage is unavailable — contact an administrator.';
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$stored")) {
            return 'Could not save the file.';
        }

        return [
            'stored_name'   => $stored,
            'original_name' => mb_substr((string) $file['name'], 0, 255),
            'mime_type'     => $mime,
            'size_bytes'    => (int) $file['size'],
        ];
    }

    public static function delete(string $subdir, ?string $storedName): void
    {
        if ($storedName === null || $storedName === '') {
            return;
        }
        $path = self::dir($subdir) . '/' . $storedName;
        if (is_file($path)) {
            unlink($path);
        }
    }

    /** Streams a stored file as an attachment. Callers MUST authorise before calling this. */
    public static function stream(string $subdir, string $storedName, string $mime, string $downloadName): void
    {
        $path = self::dir($subdir) . '/' . $storedName;
        if (!is_file($path)) {
            http_response_code(404);
            echo 'File missing from storage.';
            exit;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
        }
        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
