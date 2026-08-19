<?php
declare(strict_types=1);

final class Course
{
    public static function all(bool $publishedOnly = false): array
    {
        $sql = 'SELECT c.*,
                  (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) AS module_count,
                  (SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = c.id) AS lesson_count
                FROM courses c';
        if ($publishedOnly) {
            $sql .= " WHERE c.status = 'published'";
        }
        return Database::all($sql . ' ORDER BY c.title');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM courses WHERE id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM courses WHERE slug = :s', ['s' => $slug]);
    }

    /** The full outline: modules, each with its lessons. README §19. */
    public static function outline(int $courseId): array
    {
        $modules = Database::all('SELECT * FROM modules WHERE course_id = :c ORDER BY sort_order, id', ['c' => $courseId]);
        foreach ($modules as &$m) {
            $m['lessons'] = Database::all(
                'SELECT * FROM lessons WHERE module_id = :m ORDER BY sort_order, id',
                ['m' => $m['id']]
            );
        }
        return $modules;
    }

    /** Flat, ordered lesson list — used for progress maths and next/previous navigation. */
    public static function lessonIds(int $courseId): array
    {
        $rows = Database::all(
            'SELECT l.id FROM lessons l JOIN modules m ON m.id = l.module_id
             WHERE m.course_id = :c ORDER BY m.sort_order, m.id, l.sort_order, l.id',
            ['c' => $courseId]
        );
        return array_map('intval', array_column($rows, 'id'));
    }

    public static function programmesFor(int $courseId): array
    {
        return Database::all(
            'SELECT p.* FROM programme_courses pc JOIN programmes p ON p.id = pc.programme_id
             WHERE pc.course_id = :c ORDER BY p.title',
            ['c' => $courseId]
        );
    }

    public static function forProgramme(int $programmeId): array
    {
        return Database::all(
            'SELECT c.*, pc.sort_order, pc.is_required FROM programme_courses pc
             JOIN courses c ON c.id = pc.course_id
             WHERE pc.programme_id = :p ORDER BY pc.sort_order, c.title',
            ['p' => $programmeId]
        );
    }

    /** Courses the user can actually learn from: via an active enrolment's programme. */
    public static function forUser(int $userId): array
    {
        return Database::all(
            "SELECT DISTINCT c.*, e.id AS enrolment_id, p.title AS programme_title
             FROM enrolments e
             JOIN programme_courses pc ON pc.programme_id = e.programme_id
             JOIN courses c ON c.id = pc.course_id
             JOIN programmes p ON p.id = e.programme_id
             WHERE e.user_id = :u AND e.status IN ('active','completed') AND c.status = 'published'
             ORDER BY c.title",
            ['u' => $userId]
        );
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO courses (title, slug, description, objectives, prerequisites, standalone, status, created_by)
             VALUES (:title,:slug,:description,:objectives,:prerequisites,:standalone,:status,:created_by)',
            $data
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::query(
            'UPDATE courses SET title=:title, description=:description, objectives=:objectives,
                    prerequisites=:prerequisites, standalone=:standalone, status=:status WHERE id=:id',
            $data
        );
    }

    /** Keeps the cached duration honest after any lesson edit. */
    public static function recalcDuration(int $courseId): void
    {
        Database::query(
            'UPDATE courses c SET c.estimated_minutes = (
                SELECT COALESCE(SUM(l.duration_minutes),0) FROM lessons l
                JOIN modules m ON m.id = l.module_id WHERE m.course_id = c.id
             ) WHERE c.id = :c',
            ['c' => $courseId]
        );
    }

    public static function setCourses(int $programmeId, array $courseIds): void
    {
        Database::query('DELETE FROM programme_courses WHERE programme_id = :p', ['p' => $programmeId]);
        foreach (array_values($courseIds) as $i => $cid) {
            Database::query(
                'INSERT INTO programme_courses (programme_id, course_id, sort_order) VALUES (:p,:c,:s)',
                ['p' => $programmeId, 'c' => (int) $cid, 's' => $i]
            );
        }
    }
}
