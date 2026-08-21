<?php
declare(strict_types=1);

/**
 * Assessments — authoring, sitting, marking and results (README §18-§20).
 *
 * Two audiences share this controller and they are gated differently:
 *
 *   AUTHOR / MARKER  `education.assessment.manage` / `.grade` — permission, 403 on fail.
 *   CANDIDATE        enrolled in a programme containing the course, and holding the
 *                    `assessments` entitlement — 403 and 402 respectively.
 *
 * Learning::requireCourseAccess() already encodes the first half of the candidate rule;
 * the entitlement is checked on top of it, exactly as assignments do.
 */
final class AssessmentController
{
    // ================================================================== authoring

    /** Manage the assessments attached to one course. */
    public static function manage(): void
    {
        Auth::requirePermission('education.assessment.manage');
        $courseId = (int) ($_GET['course'] ?? 0);
        $course = Course::find($courseId);
        if (!$course) {
            self::notFound('Course not found.');
            return;
        }
        $main = View::render('assessments/manage', [
            'course'      => $course,
            'assessments' => Assessment::forCourse($courseId),
            'modules'     => Database::all('SELECT id, title FROM modules WHERE course_id = :c ORDER BY sort_order, id', ['c' => $courseId]),
        ]);
        View::shell('courses', 'Assessments — ' . $course['title'], $main);
    }

    public static function store(): void
    {
        Auth::requirePermission('education.assessment.manage');
        Csrf::requireValid();
        $courseId = (int) $_POST['course_id'];

        $id = Assessment::create($courseId, self::formData());
        Audit::log('assessment.created', 'assessments', $id, null, ['course_id' => $courseId, 'title' => $_POST['title']]);
        Session::flash('success', 'Assessment created. Add questions before publishing.');
        header('Location: app.php?r=assessments.edit&id=' . $id);
        exit;
    }

    /** Question builder for one assessment. */
    public static function edit(): void
    {
        Auth::requirePermission('education.assessment.manage');
        $assessment = Assessment::find((int) ($_GET['id'] ?? 0));
        if (!$assessment) {
            self::notFound('Assessment not found.');
            return;
        }
        $main = View::render('assessments/edit', [
            'assessment' => $assessment,
            'questions'  => Assessment::questions((int) $assessment['id']),
            'modules'    => Database::all('SELECT id, title FROM modules WHERE course_id = :c ORDER BY sort_order, id', ['c' => (int) $assessment['course_id']]),
        ]);
        View::shell('courses', $assessment['title'], $main);
    }

    public static function update(): void
    {
        Auth::requirePermission('education.assessment.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $before = Assessment::find($id);
        Assessment::update($id, self::formData());
        Audit::log('assessment.updated', 'assessments', $id, $before ? ['title' => $before['title']] : null, ['title' => $_POST['title']]);
        Session::flash('success', 'Saved.');
        header('Location: app.php?r=assessments.edit&id=' . $id);
        exit;
    }

    public static function status(): void
    {
        Auth::requirePermission('education.assessment.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $status = (string) $_POST['status'];
        if (!in_array($status, ['draft', 'published', 'closed'], true)) {
            Session::flash('error', 'Unknown status.');
            header('Location: app.php?r=assessments.edit&id=' . $id);
            exit;
        }

        if ($status === 'published') {
            $blocker = Assessment::publishBlocker($id);
            if ($blocker !== null) {
                Session::flash('error', $blocker);
                header('Location: app.php?r=assessments.edit&id=' . $id);
                exit;
            }
        }

        $before = Assessment::find($id);
        Assessment::setStatus($id, $status);
        Audit::log('assessment.status_changed', 'assessments', $id, ['status' => $before['status'] ?? null], ['status' => $status]);

        // Publishing tells the people who have to sit it. Nobody discovers an exam by
        // refreshing the course page on the off-chance.
        if ($status === 'published' && ($before['status'] ?? '') !== 'published') {
            self::notifyEnrolled($before ?? Assessment::find($id));
        }

        Session::flash('success', 'Assessment ' . $status . '.');
        header('Location: app.php?r=assessments.edit&id=' . $id);
        exit;
    }

    public static function storeQuestion(): void
    {
        Auth::requirePermission('education.assessment.manage');
        Csrf::requireValid();
        $assessmentId = (int) $_POST['assessment_id'];
        $type = (string) $_POST['type'];
        if (!array_key_exists($type, Assessment::QUESTION_TYPES)) {
            Session::flash('error', 'Unknown question type.');
            header('Location: app.php?r=assessments.edit&id=' . $assessmentId);
            exit;
        }

        $prompt = trim((string) $_POST['prompt']);
        if ($prompt === '') {
            Session::flash('error', 'A question needs a prompt.');
            header('Location: app.php?r=assessments.edit&id=' . $assessmentId);
            exit;
        }

        $questionId = Assessment::addQuestion($assessmentId, [
            'type'            => $type,
            'prompt'          => $prompt,
            'points'          => max(0, (int) ($_POST['points'] ?? 1)),
            'expected_answer' => trim((string) ($_POST['expected_answer'] ?? '')),
            'explanation'     => trim((string) ($_POST['explanation'] ?? '')),
        ]);

        // Choice questions arrive with their options in the same form — one round trip
        // rather than "create the question, now add four options one at a time".
        if (in_array($type, ['single_choice', 'multi_choice'], true)) {
            $labels  = (array) ($_POST['option_label'] ?? []);
            $correct = array_map('intval', (array) ($_POST['option_correct'] ?? []));
            $added = 0;
            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                Assessment::addOption($questionId, $label, in_array($i, $correct, true), $i + 1);
                $added++;
            }
            if ($added === 0) {
                Assessment::deleteQuestion($questionId);
                Session::flash('error', 'A choice question needs at least one option.');
                header('Location: app.php?r=assessments.edit&id=' . $assessmentId);
                exit;
            }
        }

        Audit::log('assessment.question_added', 'assessment_questions', $questionId, null, ['assessment_id' => $assessmentId, 'type' => $type]);
        Session::flash('success', 'Question added.');
        header('Location: app.php?r=assessments.edit&id=' . $assessmentId);
        exit;
    }

    public static function deleteQuestion(): void
    {
        Auth::requirePermission('education.assessment.manage');
        Csrf::requireValid();
        $questionId = (int) $_POST['question_id'];
        $question = Assessment::findQuestion($questionId);
        if ($question) {
            Assessment::deleteQuestion($questionId);
            Audit::log('assessment.question_deleted', 'assessment_questions', $questionId, ['prompt' => $question['prompt']], null);
            Session::flash('success', 'Question removed.');
        }
        header('Location: app.php?r=assessments.edit&id=' . (int) $_POST['assessment_id']);
        exit;
    }

    /** Every attempt at one assessment — the instructor's results table. */
    public static function attempts(): void
    {
        Auth::requirePermission('education.assessment.results');
        $assessment = Assessment::find((int) ($_GET['id'] ?? 0));
        if (!$assessment) {
            self::notFound('Assessment not found.');
            return;
        }
        $main = View::render('assessments/attempts', [
            'assessment' => $assessment,
            'attempts'   => Assessment::attemptsFor((int) $assessment['id']),
        ]);
        View::shell('grading', 'Results — ' . $assessment['title'], $main);
    }

    // ================================================================= candidate

    /** Start or resume a sitting. */
    public static function start(): void
    {
        Csrf::requireValid();
        $assessment = Assessment::find((int) $_POST['assessment_id']);
        if (!$assessment) {
            self::notFound('Assessment not found.');
            return;
        }
        $enrolment = Learning::requireCourseAccess((int) $assessment['course_id']);
        Entitlements::requireFeature('assessments');

        [$attempt, $error] = Assessment::startAttempt(
            (int) $assessment['id'],
            (int) Auth::id(),
            isset($enrolment['id']) ? (int) $enrolment['id'] : null
        );

        if ($error !== null) {
            Session::flash('error', $error);
            header('Location: app.php?r=learn.course&id=' . $assessment['course_id']);
            exit;
        }
        Audit::log('assessment.attempt_started', 'assessment_attempts', (int) $attempt['id'], null, ['assessment_id' => $assessment['id']]);
        header('Location: app.php?r=assessments.take&id=' . $attempt['id']);
        exit;
    }

    /** The paper itself. */
    public static function take(): void
    {
        $attempt = Assessment::findAttempt((int) ($_GET['id'] ?? 0));
        if (!$attempt || (int) $attempt['user_id'] !== (int) Auth::id()) {
            self::forbidden();
            return;
        }
        $assessment = Assessment::find((int) $attempt['assessment_id']);
        Learning::requireCourseAccess((int) $assessment['course_id']);
        Entitlements::requireFeature('assessments');

        if ($attempt['status'] !== 'in_progress') {
            header('Location: app.php?r=assessments.result&id=' . $attempt['id']);
            exit;
        }
        // Someone who leaves the tab open past the deadline gets it closed on arrival,
        // not an extra ten minutes.
        if (Assessment::hasExpired($attempt, $assessment)) {
            Assessment::finalise((int) $attempt['id'], true);
            Session::flash('error', 'Time expired — your attempt was submitted automatically.');
            header('Location: app.php?r=assessments.result&id=' . $attempt['id']);
            exit;
        }

        $main = View::render('assessments/take', [
            'assessment' => $assessment,
            'attempt'    => $attempt,
            'questions'  => Assessment::questionsForTaking((int) $assessment['id'], (int) $assessment['shuffle_questions'] === 1),
            'remaining'  => Assessment::secondsRemaining($attempt, $assessment),
        ]);
        View::shell('learn', $assessment['title'], $main);
    }

    public static function submit(): void
    {
        Csrf::requireValid();
        $attempt = Assessment::findAttempt((int) $_POST['attempt_id']);
        if (!$attempt || (int) $attempt['user_id'] !== (int) Auth::id()) {
            self::forbidden();
            return;
        }
        if ($attempt['status'] !== 'in_progress') {
            header('Location: app.php?r=assessments.result&id=' . $attempt['id']);
            exit;
        }
        $assessment = Assessment::find((int) $attempt['assessment_id']);
        Learning::requireCourseAccess((int) $assessment['course_id']);

        // A late submission is still recorded — the answers are marked as given. Silently
        // discarding a candidate's work because the clock ran out mid-save is the worse
        // failure; `time_spent_seconds` preserves the evidence for anyone reviewing.
        $responses = (array) ($_POST['q'] ?? []);
        Assessment::submit((int) $attempt['id'], $responses);

        $after = Assessment::findAttempt((int) $attempt['id']);
        Audit::log('assessment.submitted', 'assessment_attempts', (int) $attempt['id'], null, [
            'assessment_id' => $assessment['id'],
            'status'        => $after['status'] ?? null,
            'score_percent' => $after['score_percent'] ?? null,
        ]);

        Session::flash('success', ($after['needs_manual_grade'] ?? 0)
            ? 'Submitted. Some answers need marking by your instructor.'
            : 'Submitted.');
        header('Location: app.php?r=assessments.result&id=' . $attempt['id']);
        exit;
    }

    /** A candidate's own result, or an instructor reviewing it. */
    public static function result(): void
    {
        $attempt = Assessment::findAttempt((int) ($_GET['id'] ?? 0));
        if (!$attempt) {
            self::notFound('Attempt not found.');
            return;
        }
        $isOwner = (int) $attempt['user_id'] === (int) Auth::id();
        $isMarker = Auth::can('education.assessment.grade') || Auth::can('education.assessment.results');
        if (!$isOwner && !$isMarker) {
            self::forbidden();
            return;
        }

        $assessment = Assessment::find((int) $attempt['assessment_id']);
        $visible = $isMarker || Assessment::resultsVisible($assessment, $attempt);

        $main = View::render('assessments/result', [
            'assessment' => $assessment,
            'attempt'    => $attempt,
            'visible'    => $visible,
            // Correct answers are revealed only once the mark itself is visible —
            // otherwise a candidate with a second attempt left could read the answer key
            // off their first result page.
            'answers'    => $visible ? Assessment::answersFor((int) $attempt['id'], true) : [],
            'isMarker'   => $isMarker,
            'isOwner'    => $isOwner,
        ]);
        View::shell($isOwner ? 'learn' : 'grading', 'Result — ' . $assessment['title'], $main);
    }

    // =================================================================== marking

    /** Essays waiting on a human, scoped to the courses this instructor teaches. */
    public static function markingQueue(): void
    {
        Auth::requirePermission('education.assessment.grade');
        $courseIds = Auth::isSuperAdmin() || Auth::can('education.course.update')
            ? null
            : Learning::courseIdsForInstructor((int) Auth::id());

        $main = View::render('assessments/marking', [
            'attempts' => Assessment::markingQueue($courseIds),
        ]);
        View::shell('grading', 'Assessment Marking', $main);
    }

    /** Mark one attempt's essay answers. */
    public static function mark(): void
    {
        Auth::requirePermission('education.assessment.grade');
        $attempt = Assessment::findAttempt((int) ($_GET['id'] ?? 0));
        if (!$attempt) {
            self::notFound('Attempt not found.');
            return;
        }
        self::assertGraderInScope($attempt);
        $main = View::render('assessments/mark', [
            'assessment' => Assessment::find((int) $attempt['assessment_id']),
            'attempt'    => $attempt,
            'answers'    => Assessment::answersFor((int) $attempt['id'], true),
        ]);
        View::shell('grading', 'Mark — ' . $attempt['title'], $main);
    }

    public static function saveMarks(): void
    {
        Auth::requirePermission('education.assessment.grade');
        Csrf::requireValid();
        $attemptId = (int) $_POST['attempt_id'];
        $attempt = Assessment::findAttempt($attemptId);
        if (!$attempt) {
            self::notFound('Attempt not found.');
            return;
        }
        self::assertGraderInScope($attempt);

        $points   = (array) ($_POST['points'] ?? []);
        $feedback = (array) ($_POST['feedback'] ?? []);

        foreach (Assessment::answersFor($attemptId) as $answer) {
            $id = (int) $answer['id'];
            if (!array_key_exists($id, $points) || $points[$id] === '') {
                continue; // left blank — still awaiting a decision, so leave it ungraded
            }
            // Clamp rather than trust: a marker cannot award 20 out of 5.
            $awarded = max(0, min((int) $answer['points'], (int) $points[$id]));
            Assessment::gradeAnswer($id, $awarded, trim((string) ($feedback[$id] ?? '')));
        }

        Assessment::finalise($attemptId);
        $after = Assessment::findAttempt($attemptId);

        Audit::log('assessment.marked', 'assessment_attempts', $attemptId, null, [
            'score_percent' => $after['score_percent'] ?? null,
            'status'        => $after['status'] ?? null,
        ]);

        if (($after['status'] ?? '') === 'graded') {
            Notify::send(
                (int) $attempt['user_id'],
                'assessment.graded',
                'learning',
                'Your ' . $attempt['title'] . ' result is ready',
                'You scored ' . rtrim(rtrim((string) $after['score_percent'], '0'), '.') . '%.',
                'app.php?r=assessments.result&id=' . $attemptId
            );
            Session::flash('success', 'Marked and released.');
        } else {
            Session::flash('success', 'Marks saved. Some answers are still ungraded.');
        }

        header('Location: app.php?r=assessments.marking');
        exit;
    }

    // =================================================================== helpers

    /** @return array<string,mixed> */
    private static function formData(): array
    {
        $type = (string) ($_POST['type'] ?? 'quiz');
        $show = (string) ($_POST['show_results'] ?? 'immediately');
        return [
            'module_id'         => (int) ($_POST['module_id'] ?? 0) ?: null,
            'title'             => trim((string) $_POST['title']),
            'instructions'      => trim((string) ($_POST['instructions'] ?? '')),
            'type'              => in_array($type, ['quiz', 'exam'], true) ? $type : 'quiz',
            'opens_at'          => $_POST['opens_at'] ?: null,
            'closes_at'         => $_POST['closes_at'] ?: null,
            'duration_minutes'  => (int) ($_POST['duration_minutes'] ?? 0) ?: null,
            'max_attempts'      => max(0, (int) ($_POST['max_attempts'] ?? 1)),
            'pass_mark'         => max(0, min(100, (int) ($_POST['pass_mark'] ?? 50))),
            'shuffle_questions' => isset($_POST['shuffle_questions']) ? 1 : 0,
            'show_results'      => in_array($show, ['immediately', 'after_close', 'never'], true) ? $show : 'immediately',
        ];
    }

    /** Tells everyone enrolled on a programme containing this course that a paper is live. */
    private static function notifyEnrolled(array $assessment): void
    {
        $rows = Database::all(
            "SELECT DISTINCT e.user_id FROM enrolments e
             JOIN programme_courses pc ON pc.programme_id = e.programme_id
             WHERE pc.course_id = :c AND e.status = 'active'",
            ['c' => (int) $assessment['course_id']]
        );
        $userIds = array_map('intval', array_column($rows, 'user_id'));
        if ($userIds === []) {
            return;
        }
        Notify::sendMany(
            $userIds,
            'assessment.published',
            'learning',
            'New ' . ($assessment['type'] === 'exam' ? 'examination' : 'quiz') . ': ' . $assessment['title'],
            $assessment['closes_at'] ? 'Closes ' . date('d M Y H:i', strtotime((string) $assessment['closes_at'])) . '.' : null,
            'app.php?r=learn.course&id=' . $assessment['course_id']
        );
    }

    private static function notFound(string $message): void
    {
        http_response_code(404);
        echo View::e($message);
    }

    private static function forbidden(): void
    {
        http_response_code(403);
        require dirname(__DIR__) . '/views/errors/403.php';
    }

    /**
     * education.assessment.grade is ◐ for instructors (03-rbac.md), scoped to the
     * courses they actually teach — same rule markingQueue() already applies to the
     * listing via Learning::courseIdsForInstructor(). mark()/saveMarks() reach a specific
     * attempt by id and must apply the identical check, or an instructor could mark any
     * course's attempts just by knowing/guessing the id.
     */
    private static function assertGraderInScope(array $attempt): void
    {
        if (Auth::isSuperAdmin() || Auth::can('education.course.update')) {
            return;
        }
        $courseIds = Learning::courseIdsForInstructor((int) Auth::id());
        if (!in_array((int) $attempt['course_id'], $courseIds, true)) {
            self::forbidden();
            exit;
        }
    }
}
