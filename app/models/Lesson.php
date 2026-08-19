<?php
declare(strict_types=1);

final class Lesson
{
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT l.*, m.course_id, m.title AS module_title, c.title AS course_title, c.status AS course_status
             FROM lessons l JOIN modules m ON m.id = l.module_id JOIN courses c ON c.id = m.course_id
             WHERE l.id = :id',
            ['id' => $id]
        );
    }

    public static function create(int $moduleId, array $data): int
    {
        $next = Database::one('SELECT COALESCE(MAX(sort_order),-1)+1 n FROM lessons WHERE module_id = :m', ['m' => $moduleId])['n'];
        Database::query(
            'INSERT INTO lessons (module_id, title, content_type, body, duration_minutes, sort_order, is_preview)
             VALUES (:m,:title,:ct,:body,:dur,:sort,:preview)',
            [
                'm' => $moduleId, 'title' => $data['title'], 'ct' => $data['content_type'],
                'body' => $data['body'], 'dur' => $data['duration_minutes'],
                'sort' => (int) $next, 'preview' => $data['is_preview'],
            ]
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::query(
            'UPDATE lessons SET title=:title, content_type=:content_type, body=:body,
                    duration_minutes=:duration_minutes, is_preview=:is_preview WHERE id=:id',
            $data
        );
    }

    public static function delete(int $id): void
    {
        foreach (Material::forLesson($id) as $m) {
            Material::delete((int) $m['id']);
        }
        Database::query('DELETE FROM lessons WHERE id = :id', ['id' => $id]);
    }

    public static function createModule(int $courseId, string $title, ?string $summary): int
    {
        $next = Database::one('SELECT COALESCE(MAX(sort_order),-1)+1 n FROM modules WHERE course_id = :c', ['c' => $courseId])['n'];
        Database::query(
            'INSERT INTO modules (course_id, title, summary, sort_order) VALUES (:c,:t,:s,:o)',
            ['c' => $courseId, 't' => $title, 's' => $summary, 'o' => (int) $next]
        );
        return Database::lastInsertId();
    }

    public static function findModule(int $id): ?array
    {
        return Database::one('SELECT * FROM modules WHERE id = :id', ['id' => $id]);
    }

    public static function deleteModule(int $id): void
    {
        Database::query('DELETE FROM modules WHERE id = :id', ['id' => $id]);
    }
}
