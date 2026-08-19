<?php
declare(strict_types=1);

/** Marking submissions — README §23 "grading, feedback, scores". */
final class GradingController
{
    public static function queue(): void
    {
        Auth::requirePermission('education.assignment.grade');

        // `education.assignment.grade` is ◐ for instructors in 03-rbac.md §5 — scoped to
        // their own classes. Course scope comes from the cohorts they actually teach, not
        // from a centre, because a course is not a place.
        $courseIds = Auth::isSuperAdmin() ? null : Learning::courseIdsForInstructor((int) Auth::id());

        $main = View::render('grading/queue', [
            'submissions' => Assignment::gradingQueue($courseIds),
            'scoped' => $courseIds !== null,
        ]);
        View::shell('grading', 'Grading', $main);
    }

    public static function show(): void
    {
        Auth::requirePermission('education.assignment.grade');
        $submission = Assignment::findSubmission((int) ($_GET['id'] ?? 0));
        if (!$submission) {
            http_response_code(404);
            echo 'Submission not found.';
            return;
        }
        self::assertInScope($submission);

        $main = View::render('grading/show', ['submission' => $submission]);
        View::shell('grading', 'Grade submission', $main);
    }

    public static function grade(): void
    {
        Auth::requirePermission('education.assignment.grade');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $submission = Assignment::findSubmission($id);
        if (!$submission) {
            http_response_code(404);
            echo 'Submission not found.';
            return;
        }
        self::assertInScope($submission);

        $max = (int) $submission['max_score'];
        $score = (int) ($_POST['score'] ?? 0);
        if ($score < 0 || $score > $max) {
            Session::flash('error', "Score must be between 0 and $max.");
            header('Location: app.php?r=grading.show&id=' . $id);
            exit;
        }

        $feedback = trim((string) ($_POST['feedback'] ?? ''));
        Assignment::grade($id, $score, $feedback);
        Audit::log('assignment.graded', 'assignment_submissions', $id, null, ['score' => $score, 'max' => $max]);

        Notify::send((int) $submission['user_id'], 'assignment.graded', 'learning',
            'Your assignment was graded',
            $submission['assignment_title'] . ' — ' . $score . '/' . $max
                . ($feedback !== '' ? '. Feedback provided.' : '.'),
            'app.php?r=learn');
        Session::flash('success', "Graded $score/$max.");
        header('Location: app.php?r=grading');
        exit;
    }

    private static function assertInScope(array $submission): void
    {
        if (Auth::isSuperAdmin()) {
            return;
        }
        $courseIds = Learning::courseIdsForInstructor((int) Auth::id());
        if (!in_array((int) $submission['course_id'], $courseIds, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
    }
}
