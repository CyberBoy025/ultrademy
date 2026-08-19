<?php
declare(strict_types=1);

/**
 * Who may see course content, and on what grounds.
 *
 * There are two different answers, and conflating them breaks one group or the other:
 *
 *   STAFF   — hold `education.lesson.view` (or `.course.update`). No entitlement check.
 *             An instructor must not have to buy Premium to open the course they teach;
 *             04-subscriptions Decision 16 gives staff operational features only, and
 *             `online_learning` is deliberately NOT one of them.
 *
 *   LEARNER — must be enrolled in a programme that includes the course, AND hold the
 *             `online_learning` entitlement. Failing the first is 403 (you are not on
 *             this course); failing the second is 402 (your package does not include
 *             online learning). Those are different problems with different fixes.
 *
 * Preview lessons (`lessons.is_preview`) are the deliberate exception — visible to any
 * signed-in user so a course can advertise itself.
 */
final class Learning
{
    public static function isStaffViewer(): bool
    {
        return Auth::can('education.lesson.view') || Auth::can('education.course.update');
    }

    public static function canManage(): bool
    {
        return Auth::can('education.course.update');
    }

    /** The user's active enrolment covering this course, if any. */
    public static function enrolmentForCourse(int $courseId, ?int $userId = null): ?array
    {
        $userId ??= (int) Auth::id();
        return Database::one(
            "SELECT e.* FROM enrolments e
             JOIN programme_courses pc ON pc.programme_id = e.programme_id
             WHERE e.user_id = :u AND pc.course_id = :c AND e.status IN ('active','completed')
             LIMIT 1",
            ['u' => $userId, 'c' => $courseId]
        );
    }

    public static function isEnrolledIn(int $courseId, ?int $userId = null): bool
    {
        return self::enrolmentForCourse($courseId, $userId) !== null;
    }

    /**
     * Gate for reading a course. Exits with 403 or 402 as appropriate.
     * Returns the learner's enrolment (null for staff, or for a preview viewer).
     */
    public static function requireCourseAccess(int $courseId): ?array
    {
        if (self::isStaffViewer()) {
            return null;
        }

        $enrolment = self::enrolmentForCourse($courseId);
        if ($enrolment === null) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }

        // Enrolled, but the package may not include online learning.
        Entitlements::requireFeature('online_learning');
        return $enrolment;
    }

    /** A lesson flagged as preview is readable by any signed-in user. */
    public static function requireLessonAccess(array $lesson): ?array
    {
        if ((int) $lesson['is_preview'] === 1 || self::isStaffViewer()) {
            return self::isStaffViewer() ? null : self::enrolmentForCourse((int) $lesson['course_id']);
        }
        return self::requireCourseAccess((int) $lesson['course_id']);
    }

    /** Courses an instructor is responsible for, via the cohorts they teach. */
    public static function courseIdsForInstructor(int $userId): array
    {
        $rows = Database::all(
            'SELECT DISTINCT pc.course_id FROM class_groups cg
             JOIN cohorts co ON co.id = cg.cohort_id
             JOIN programme_courses pc ON pc.programme_id = co.programme_id
             WHERE cg.instructor_user_id = :u',
            ['u' => $userId]
        );
        return array_map('intval', array_column($rows, 'course_id'));
    }
}
