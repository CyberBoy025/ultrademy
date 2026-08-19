<?php
declare(strict_types=1);

/**
 * Interview scheduling (recruitment.interview.manage) and feedback capture
 * (recruitment.interview.feedback, brief §23 — internal only, never applicant-visible).
 */
final class InterviewController
{
    public static function store(): void
    {
        Auth::requirePermission('recruitment.interview.manage');
        Csrf::requireValid();
        $appId = (int) $_POST['job_application_id'];
        $app = JobApplication::find($appId);
        if (!$app) {
            http_response_code(404);
            echo 'Application not found.';
            return;
        }

        $type = isset(Interview::TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : 'online';
        $panelistIds = array_map('intval', $_POST['panelist_ids'] ?? []);
        $interviewId = Interview::create(
            $appId, trim((string) ($_POST['scheduled_at'] ?? '')) ?: null, $type,
            trim((string) ($_POST['location'] ?? '')), trim((string) ($_POST['meeting_link'] ?? '')),
            trim((string) ($_POST['instructions'] ?? '')), $panelistIds
        );

        if (!in_array($app['status'], ['interview'], true)) {
            JobApplication::setStatus($appId, 'interview', (int) Auth::id());
            JobApplicationStatusHistory::record($appId, $app['status'], 'interview', (int) Auth::id());
        }

        Audit::log('interview.scheduled', 'interviews', $interviewId, null, ['job_application_id' => $appId]);

        $interview = Interview::find($interviewId);
        $mail = EmailTemplate::render('interview_invitation', JobApplication::emailVars($app, $interview));
        Notify::send((int) $app['user_id'], 'interview.scheduled', 'recruitment', $mail['subject'], $mail['body'], null);
        RecruitmentEmailLog::record($appId, $app['email'], $mail['subject'], 'interview_invitation', 'interview.scheduled');

        Notify::sendMany($panelistIds, 'interview.assigned', 'recruitment',
            'You have been assigned to an interview',
            'You are on the panel for an interview — ' . $app['job_title'] . ' (' . $app['reference'] . ').', null);

        Session::flash('success', 'Interview scheduled.');
        header('Location: app.php?r=recruitment.applications.show&id=' . $appId);
        exit;
    }

    /** Date/time change — keeps the interview `scheduled`, notifies the applicant of the new time. */
    public static function reschedule(): void
    {
        Auth::requirePermission('recruitment.interview.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $interview = Interview::find($id);
        if (!$interview) {
            http_response_code(404);
            echo 'Interview not found.';
            return;
        }
        $newTime = trim((string) ($_POST['scheduled_at'] ?? ''));
        if ($newTime === '') {
            Session::flash('error', 'Choose a new date and time.');
            header('Location: app.php?r=recruitment.applications.show&id=' . $interview['job_application_id']);
            exit;
        }
        Interview::reschedule($id, $newTime);
        Audit::log('interview.rescheduled', 'interviews', $id, ['scheduled_at' => $interview['scheduled_at']], ['scheduled_at' => $newTime]);

        $app = JobApplication::find((int) $interview['job_application_id']);
        $updated = Interview::find($id);
        $mail = EmailTemplate::render('interview_rescheduled', JobApplication::emailVars($app, $updated));
        Notify::send((int) $app['user_id'], 'interview.rescheduled', 'recruitment', $mail['subject'], $mail['body'], null);
        RecruitmentEmailLog::record((int) $app['id'], $app['email'], $mail['subject'], 'interview_rescheduled', 'interview.rescheduled');

        Session::flash('success', 'Interview rescheduled.');
        header('Location: app.php?r=recruitment.applications.show&id=' . $interview['job_application_id']);
        exit;
    }

    /** Completed and cancelled are the only direct status transitions — scheduling sets `scheduled`, reschedule() resets it. */
    public static function updateStatus(): void
    {
        Auth::requirePermission('recruitment.interview.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $interview = Interview::find($id);
        if (!$interview) {
            http_response_code(404);
            echo 'Interview not found.';
            return;
        }
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['completed', 'cancelled'], true)) {
            Session::flash('error', 'Invalid status.');
            header('Location: app.php?r=recruitment.applications.show&id=' . $interview['job_application_id']);
            exit;
        }
        Interview::setStatus($id, $status);
        Audit::log('interview.status_changed', 'interviews', $id, ['status' => $interview['status']], ['status' => $status]);

        if ($status === 'cancelled') {
            $app = JobApplication::find((int) $interview['job_application_id']);
            $mail = EmailTemplate::render('interview_cancellation', JobApplication::emailVars($app, $interview));
            Notify::send((int) $app['user_id'], 'interview.cancelled', 'recruitment', $mail['subject'], $mail['body'], null);
            RecruitmentEmailLog::record((int) $app['id'], $app['email'], $mail['subject'], 'interview_cancellation', 'interview.cancelled');
        }

        Session::flash('success', 'Interview updated.');
        header('Location: app.php?r=recruitment.applications.show&id=' . $interview['job_application_id']);
        exit;
    }

    // -------------------------------------------------------------------------- feedback

    /** Panelists assigned to their own interviews — no view_any needed, ownership is the gate. */
    public static function myInterviews(): void
    {
        Auth::requirePermission('recruitment.interview.feedback');
        $main = View::render('recruitment/interviews/mine', ['interviews' => Interview::forPanelist((int) Auth::id())]);
        View::shell('recruitment', 'My Interviews', $main);
    }

    public static function feedback(): void
    {
        Auth::requirePermission('recruitment.interview.feedback');
        $interview = self::loadOwnPanelInterviewOrDeny((int) ($_GET['id'] ?? 0));
        $app = JobApplication::find((int) $interview['job_application_id']);
        $main = View::render('recruitment/interviews/feedback', [
            'interview' => $interview, 'app' => $app,
            'existing' => Database::one('SELECT * FROM interview_feedback WHERE interview_id = :i AND panelist_user_id = :u', ['i' => $interview['id'], 'u' => Auth::id()]),
        ]);
        View::shell('recruitment', 'Interview Feedback', $main);
    }

    public static function feedbackStore(): void
    {
        Auth::requirePermission('recruitment.interview.feedback');
        Csrf::requireValid();
        $interview = self::loadOwnPanelInterviewOrDeny((int) $_POST['interview_id']);

        InterviewFeedback::submit(
            (int) $interview['id'], (int) Auth::id(),
            $_POST['score'] !== '' ? (int) $_POST['score'] : null,
            trim((string) ($_POST['evaluation'] ?? '')), trim((string) ($_POST['strengths'] ?? '')),
            trim((string) ($_POST['concerns'] ?? '')), (string) ($_POST['recommendation'] ?? '')
        );
        Audit::log('interview.feedback_submitted', 'interviews', (int) $interview['id']);
        Session::flash('success', 'Feedback saved.');
        header('Location: app.php?r=recruitment.interviews.mine');
        exit;
    }

    /** @return array<string,mixed> the interview, or exits 403/404 */
    private static function loadOwnPanelInterviewOrDeny(int $interviewId): array
    {
        $interview = Interview::find($interviewId);
        if (!$interview) {
            http_response_code(404);
            echo 'Interview not found.';
            exit;
        }
        if (!Auth::isSuperAdmin() && !Interview::isPanelist($interviewId, (int) Auth::id())) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
        return $interview;
    }
}
