<?php
declare(strict_types=1);

/** Lesson-level progress, and the course percentages derived from it (README §19, §20). */
final class Progress
{
    /** @return array<int,array{progress_pct:int,completed_at:?string}> lesson_id => row */
    public static function forCourse(int $userId, int $courseId): array
    {
        $rows = Database::all(
            'SELECT lp.lesson_id, lp.progress_pct, lp.completed_at
             FROM lesson_progress lp
             JOIN lessons l ON l.id = lp.lesson_id
             JOIN modules m ON m.id = l.module_id
             WHERE lp.user_id = :u AND m.course_id = :c',
            ['u' => $userId, 'c' => $courseId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['lesson_id']] = [
                'progress_pct' => (int) $r['progress_pct'],
                'completed_at' => $r['completed_at'],
            ];
        }
        return $out;
    }

    public static function markComplete(int $userId, int $lessonId, ?int $enrolmentId): void
    {
        Database::query(
            'INSERT INTO lesson_progress (user_id, lesson_id, enrolment_id, progress_pct, completed_at)
             VALUES (:u,:l,:e,100,NOW())
             ON DUPLICATE KEY UPDATE progress_pct = 100, completed_at = COALESCE(completed_at, NOW()), enrolment_id = COALESCE(VALUES(enrolment_id), enrolment_id)',
            ['u' => $userId, 'l' => $lessonId, 'e' => $enrolmentId]
        );
    }

    public static function markIncomplete(int $userId, int $lessonId): void
    {
        Database::query(
            'UPDATE lesson_progress SET progress_pct = 0, completed_at = NULL WHERE user_id = :u AND lesson_id = :l',
            ['u' => $userId, 'l' => $lessonId]
        );
    }

    /** Percentage of a course's lessons the user has completed. 0 when the course is empty. */
    public static function coursePercent(int $userId, int $courseId): int
    {
        $row = Database::one(
            'SELECT COUNT(*) total,
                    SUM(CASE WHEN lp.completed_at IS NOT NULL THEN 1 ELSE 0 END) done
             FROM lessons l
             JOIN modules m ON m.id = l.module_id
             LEFT JOIN lesson_progress lp ON lp.lesson_id = l.id AND lp.user_id = :u
             WHERE m.course_id = :c',
            ['u' => $userId, 'c' => $courseId]
        );
        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) {
            return 0;
        }
        return (int) round(((int) $row['done']) / $total * 100);
    }

    public static function isCourseComplete(int $userId, int $courseId): bool
    {
        $lessonIds = Course::lessonIds($courseId);
        return $lessonIds !== [] && self::coursePercent($userId, $courseId) === 100;
    }

    /** Across an enrolment's whole programme — used for the programme certificate. */
    public static function programmePercent(int $userId, int $programmeId): int
    {
        $courses = Course::forProgramme($programmeId);
        if (!$courses) {
            return 0;
        }
        $sum = 0;
        foreach ($courses as $c) {
            $sum += self::coursePercent($userId, (int) $c['id']);
        }
        return (int) round($sum / count($courses));
    }
}
