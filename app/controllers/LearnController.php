<?php
declare(strict_types=1);

/** The student learning environment — README §20. */
final class LearnController
{
    public static function index(): void
    {
        $userId = (int) Auth::id();
        $courses = Course::forUser($userId);
        foreach ($courses as &$c) {
            $c['percent'] = Progress::coursePercent($userId, (int) $c['id']);
        }
        $main = View::render('learn/index', [
            'courses' => $courses,
            'entitled' => Entitlements::can('online_learning'),
            'certificates' => Certificate::forUser($userId),
            'results' => Assignment::forUser($userId),
        ]);
        View::shell('learn', 'My Learning', $main);
    }

    /** Course outline with the learner's own progress against it. */
    public static function course(): void
    {
        $courseId = (int) ($_GET['id'] ?? 0);
        $course = Course::find($courseId);
        if (!$course) {
            http_response_code(404);
            echo 'Course not found.';
            return;
        }
        $enrolment = Learning::requireCourseAccess($courseId);
        $userId = (int) Auth::id();

        $main = View::render('learn/course', [
            'course' => $course,
            'outline' => Course::outline($courseId),
            'progress' => Progress::forCourse($userId, $courseId),
            'percent' => Progress::coursePercent($userId, $courseId),
            'assignments' => Assignment::forCourse($courseId, true),
            'submissions' => self::submissionMap($userId, $courseId),
            'enrolment' => $enrolment,
            'complete' => Progress::isCourseComplete($userId, $courseId),
            'certificate' => self::courseCertificate($userId, $courseId),
            'assessments' => Assessment::forCourse($courseId, true),
            'assessmentState' => self::assessmentState($courseId, $userId),
        ]);
        View::shell('learn', $course['title'], $main);
    }

    public static function lesson(): void
    {
        $lesson = Lesson::find((int) ($_GET['id'] ?? 0));
        if (!$lesson) {
            http_response_code(404);
            echo 'Lesson not found.';
            return;
        }
        $enrolment = Learning::requireLessonAccess($lesson);
        $userId = (int) Auth::id();

        $lessonIds = Course::lessonIds((int) $lesson['course_id']);
        $pos = array_search((int) $lesson['id'], $lessonIds, true);
        $progress = Progress::forCourse($userId, (int) $lesson['course_id']);

        $main = View::render('learn/lesson', [
            'lesson' => $lesson,
            'materials' => Material::forLesson((int) $lesson['id']),
            'enrolment' => $enrolment,
            'isComplete' => isset($progress[(int) $lesson['id']]) && $progress[(int) $lesson['id']]['completed_at'] !== null,
            'prevId' => $pos !== false && $pos > 0 ? $lessonIds[$pos - 1] : null,
            'nextId' => $pos !== false && $pos < count($lessonIds) - 1 ? $lessonIds[$pos + 1] : null,
            'percent' => Progress::coursePercent($userId, (int) $lesson['course_id']),
            'isStaffViewer' => Learning::isStaffViewer(),
        ]);
        View::shell('learn', $lesson['title'], $main);
    }

    public static function toggleProgress(): void
    {
        Csrf::requireValid();
        $lesson = Lesson::find((int) $_POST['lesson_id']);
        if (!$lesson) {
            http_response_code(404);
            echo 'Lesson not found.';
            return;
        }
        $enrolment = Learning::requireCourseAccess((int) $lesson['course_id']);
        $userId = (int) Auth::id();

        if (($_POST['done'] ?? '1') === '1') {
            Progress::markComplete($userId, (int) $lesson['id'], $enrolment['id'] ?? null);
            // Finishing the last lesson earns the certificate, if the package includes them.
            if (Progress::isCourseComplete($userId, (int) $lesson['course_id']) && Entitlements::can('certificates')) {
                $certId = Certificate::issueForCourse($userId, (int) $lesson['course_id'], isset($enrolment['id']) ? (int) $enrolment['id'] : null);
                if ($certId !== null) {
                    Audit::log('certificate.issued', 'certificates', $certId, null, ['course_id' => $lesson['course_id']]);
                    Session::flash('success', 'Course complete — your certificate has been issued.');
                }
            }
        } else {
            Progress::markIncomplete($userId, (int) $lesson['id']);
        }
        header('Location: app.php?r=learn.lesson&id=' . $lesson['id']);
        exit;
    }

    // ------------------------------------------------------------------ materials

    public static function downloadMaterial(): void
    {
        $material = Material::find((int) ($_GET['id'] ?? 0));
        if (!$material) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        // Same gate as the lesson it belongs to — a direct link must not bypass it.
        Learning::requireCourseAccess((int) $material['course_id']);

        if ($material['type'] === 'link') {
            header('Location: ' . $material['url']);
            exit;
        }
        if (!Learning::isStaffViewer() && (int) $material['is_downloadable'] !== 1) {
            http_response_code(403);
            echo 'This material is view-only.';
            return;
        }
        Upload::stream(Material::SUBDIR, $material['stored_name'], $material['mime_type'], $material['original_name']);
    }

    // ---------------------------------------------------------------- assignments

    public static function submitAssignment(): void
    {
        Csrf::requireValid();
        $assignment = Assignment::find((int) $_POST['assignment_id']);
        if (!$assignment || $assignment['status'] !== 'published') {
            Session::flash('error', 'That assignment is not open for submission.');
            header('Location: app.php?r=learn');
            exit;
        }
        $enrolment = Learning::requireCourseAccess((int) $assignment['course_id']);
        Entitlements::requireFeature('assignments');

        $userId = (int) Auth::id();
        $existing = Assignment::submissionFor((int) $assignment['id'], $userId);
        if ($existing && (int) $assignment['allows_resubmission'] !== 1) {
            Session::flash('error', 'You have already submitted, and this assignment does not allow resubmission.');
            header('Location: app.php?r=learn.course&id=' . $assignment['course_id']);
            exit;
        }

        $error = Assignment::submit(
            (int) $assignment['id'],
            $userId,
            isset($enrolment['id']) ? (int) $enrolment['id'] : null,
            trim((string) ($_POST['body'] ?? '')),
            $_FILES['file'] ?? []
        );
        if ($error !== null) {
            Session::flash('error', $error);
        } else {
            Audit::log('assignment.submitted', 'assignments', (int) $assignment['id']);
            Session::flash('success', 'Submitted.');
        }
        header('Location: app.php?r=learn.course&id=' . $assignment['course_id']);
        exit;
    }

    public static function downloadSubmission(): void
    {
        $submission = Assignment::findSubmission((int) ($_GET['id'] ?? 0));
        if (!$submission || !$submission['stored_name']) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        $isOwner = (int) $submission['user_id'] === (int) Auth::id();
        if (!$isOwner && !Auth::can('education.assignment.grade')) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }
        Upload::stream(Assignment::SUBDIR, $submission['stored_name'], $submission['mime_type'], $submission['original_name']);
    }

    // --------------------------------------------------------------- certificates

    public static function certificates(): void
    {
        Entitlements::requireFeature('certificates');
        $main = View::render('learn/certificates', [
            'certificates' => Certificate::forUser((int) Auth::id()),
        ]);
        View::shell('learn', 'My Certificates', $main);
    }

    // -------------------------------------------------------------------- helpers

    private static function submissionMap(int $userId, int $courseId): array
    {
        $rows = Database::all(
            'SELECT s.* FROM assignment_submissions s JOIN assignments a ON a.id = s.assignment_id
             WHERE s.user_id = :u AND a.course_id = :c',
            ['u' => $userId, 'c' => $courseId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['assignment_id']] = $r;
        }
        return $out;
    }

    /**
     * Per-assessment state for the course page: what the learner has used, what they
     * scored, and whether they may start another sitting.
     *
     * Computed here rather than in the view so the "can I start?" rule lives in one place
     * — AssessmentController::start() re-checks it server-side, and a button that appears
     * when the server would refuse is worse than no button.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function assessmentState(int $courseId, int $userId): array
    {
        $out = [];
        foreach (Assessment::forCourse($courseId, true) as $a) {
            $id = (int) $a['id'];
            $attempts = Assessment::attemptsForUser($id, $userId);
            $used = count(array_filter($attempts, static fn(array $t): bool => $t['status'] !== 'expired'));
            $max = (int) $a['max_attempts'];
            $open = Assessment::openAttempt($id, $userId);

            $out[$id] = [
                'attempts'   => $attempts,
                'used'       => $used,
                'open'       => $open,
                'best'       => Assessment::bestPercent($id, $userId),
                'isOpen'     => Assessment::isOpen($a),
                'closedWhy'  => Assessment::closedReason($a),
                'canStart'   => Assessment::isOpen($a)
                                && (int) $a['question_count'] > 0
                                && ($max === 0 || $used < $max || $open !== null),
                'entitled'   => Entitlements::can('assessments'),
            ];
        }
        return $out;
    }

    private static function courseCertificate(int $userId, int $courseId): ?array
    {
        return Database::one(
            'SELECT * FROM certificates WHERE user_id = :u AND course_id = :c AND revoked_at IS NULL',
            ['u' => $userId, 'c' => $courseId]
        );
    }
}
