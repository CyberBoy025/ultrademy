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
    /**
     * UI-only: "should this page show the staff chrome (banner, unlocked materials)?"
     * NOT a security gate — it does not check which course. hasCourseAccess($courseId)
     * is the gate; use that for anything that decides whether a request may proceed.
     */
    public static function isStaffViewer(): bool
    {
        return Auth::can('education.lesson.view') || Auth::can('education.course.update');
    }

    /**
     * The real per-course security gate. 03-rbac.md §5 grades both `education.lesson.view`
     * and `education.course.update` `◐ (assigned)` for instructors — scoped to the courses
     * they actually teach, not every course in the system. A centre_manager's `◐` is
     * centre-scoped instead, since a course is not something one person is "assigned" to
     * the way an instructor is. Only administrator's `education.course.update` and
     * management's `education.lesson.view` are genuinely global (● in the matrix).
     */
    public static function hasCourseAccess(int $courseId): bool
    {
        if (Auth::isSuperAdmin()) {
            return true;
        }
        if (Auth::can('education.course.update') && Auth::scopeCentres('education.course.update') === null) {
            return true; // administrator's true global grant
        }
        if (Auth::can('education.lesson.view') && Auth::scopeCentres('education.lesson.view') === null) {
            return true; // management's true global grant
        }
        if (!Auth::can('education.lesson.view') && !Auth::can('education.course.update')) {
            return false; // not staff on this at all
        }

        if (in_array($courseId, self::courseIdsForInstructor((int) Auth::id()), true)) {
            return true;
        }

        $centreScope = Auth::scopeCentres('education.lesson.view');
        if ($centreScope !== null && $centreScope !== []) {
            return in_array($courseId, self::courseIdsForCentres($centreScope), true);
        }
        return false;
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
        if (self::hasCourseAccess($courseId)) {
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
        $courseId = (int) $lesson['course_id'];
        $hasAccess = self::hasCourseAccess($courseId);
        if ((int) $lesson['is_preview'] === 1 || $hasAccess) {
            return $hasAccess ? null : self::enrolmentForCourse($courseId);
        }
        return self::requireCourseAccess($courseId);
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

    /** Courses offered at any of the given centres, via the cohorts running there. */
    public static function courseIdsForCentres(array $centreIds): array
    {
        if ($centreIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($centreIds), '?'));
        $rows = Database::query(
            "SELECT DISTINCT pc.course_id FROM cohorts co
             JOIN programme_courses pc ON pc.programme_id = co.programme_id
             WHERE co.centre_id IN ($placeholders)",
            $centreIds
        )->fetchAll();
        return array_map('intval', array_column($rows, 'course_id'));
    }
}
