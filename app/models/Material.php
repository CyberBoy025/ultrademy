<?php
declare(strict_types=1);

/** Learning materials attached to a lesson (README §18, §20). */
final class Material
{
    public const SUBDIR = 'materials';
    private const MAX_BYTES = 50 * 1024 * 1024; // 50 MB — course video is bigger than an ID scan

    public static function forLesson(int $lessonId): array
    {
        return Database::all('SELECT * FROM lesson_materials WHERE lesson_id = :l ORDER BY id', ['l' => $lessonId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT lm.*, l.module_id, m.course_id FROM lesson_materials lm
             JOIN lessons l ON l.id = lm.lesson_id JOIN modules m ON m.id = l.module_id
             WHERE lm.id = :id',
            ['id' => $id]
        );
    }

    /** @return string|null error message, or null on success */
    public static function storeUpload(int $lessonId, string $title, array $file, bool $downloadable): ?string
    {
        $result = Upload::store($file, self::SUBDIR, Upload::MATERIAL_TYPES, self::MAX_BYTES);
        if (is_string($result)) {
            return $result;
        }
        $type = str_starts_with($result['mime_type'], 'video/') ? 'video' : 'document';
        Database::query(
            'INSERT INTO lesson_materials (lesson_id, type, title, stored_name, original_name, mime_type, size_bytes, is_downloadable)
             VALUES (:l,:t,:title,:stored,:orig,:mime,:size,:dl)',
            [
                'l' => $lessonId, 't' => $type, 'title' => $title,
                'stored' => $result['stored_name'], 'orig' => $result['original_name'],
                'mime' => $result['mime_type'], 'size' => $result['size_bytes'],
                'dl' => $downloadable ? 1 : 0,
            ]
        );
        return null;
    }

    public static function storeLink(int $lessonId, string $title, string $url): void
    {
        Database::query(
            "INSERT INTO lesson_materials (lesson_id, type, title, url, is_downloadable) VALUES (:l,'link',:t,:u,1)",
            ['l' => $lessonId, 't' => $title, 'u' => $url]
        );
    }

    public static function delete(int $id): void
    {
        $m = self::find($id);
        if (!$m) {
            return;
        }
        Upload::delete(self::SUBDIR, $m['stored_name']);
        Database::query('DELETE FROM lesson_materials WHERE id = :id', ['id' => $id]);
    }
}
