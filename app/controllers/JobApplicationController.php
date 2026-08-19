<?php
declare(strict_types=1);

/**
 * The apply workflow (brief §15): job -> profile check -> documents -> questions ->
 * review -> declaration -> submit -> reference number. Every step is ownership-scoped to
 * the signed-in applicant and only reachable while the application is still a draft — a
 * submitted application is locked (brief §17's default) with withdrawal as the only
 * applicant-side edit.
 *
 * Simplification worth flagging: which documents a posting requires isn't configurable
 * yet (that lands with the recruitment admin backend) — v1 requires a CV and treats
 * everything else as optional for every posting.
 */
final class JobApplicationController
{
    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            header('Location: login.php');
            exit;
        }
    }

    /** @return array<string,mixed> the draft, or exits 403/404 */
    private static function loadOwnDraftOrDeny(int $id): array
    {
        $app = JobApplication::find($id);
        if (!$app || (int) $app['user_id'] !== (int) Auth::id()) {
            http_response_code(404);
            echo 'Application not found.';
            exit;
        }
        if ($app['status'] !== 'draft') {
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }
        return $app;
    }

    public static function start(): void
    {
        self::requireLogin();
        $slug = (string) ($_GET['job'] ?? '');
        $posting = JobPosting::findPublishedBySlug($slug);
        if (!$posting || !JobPosting::isAcceptingApplications($posting)) {
            Session::flash('error', 'That position is no longer accepting applications.');
            header('Location: app.php?r=jobs');
            exit;
        }
        $draft = JobApplication::getOrCreateDraft((int) Auth::id(), (int) $posting['id']);
        header('Location: app.php?r=apply.documents&id=' . $draft['id']);
        exit;
    }

    // -------------------------------------------------------------------- documents

    public static function documents(): void
    {
        self::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $app = self::loadOwnDraftOrDeny($id);
        $main = View::render('careers/apply/documents', [
            'app' => $app,
            'documents' => JobApplicationDocument::forApplication($id),
        ]);
        View::careersShell('jobs', 'Apply — Documents', $main);
    }

    public static function documentsStore(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $id = (int) ($_POST['application_id'] ?? 0);
        self::loadOwnDraftOrDeny($id);

        $type = isset(JobApplicationDocument::TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : 'other';
        $error = JobApplicationDocument::store($id, $type, $_FILES['document'] ?? []);
        Session::flash($error === null ? 'success' : 'error', $error ?? 'Document uploaded.');
        header('Location: app.php?r=apply.documents&id=' . $id);
        exit;
    }

    public static function documentsDelete(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $docId = (int) ($_POST['id'] ?? 0);
        $doc = JobApplicationDocument::find($docId);
        if ($doc) {
            self::loadOwnDraftOrDeny((int) $doc['job_application_id']);
            JobApplicationDocument::delete($docId);
        }
        header('Location: app.php?r=apply.documents&id=' . ($doc['job_application_id'] ?? ''));
        exit;
    }

    public static function documentsDownload(): void
    {
        self::requireLogin();
        $doc = JobApplicationDocument::find((int) ($_GET['id'] ?? 0));
        if (!$doc) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        $app = JobApplication::find((int) $doc['job_application_id']);
        if (!$app || (int) $app['user_id'] !== (int) Auth::id()) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }
        $path = JobApplicationDocument::storageDir() . '/' . $doc['stored_name'];
        if (!is_file($path)) {
            http_response_code(404);
            echo 'File missing from storage.';
            return;
        }
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $doc['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    // --------------------------------------------------------------------- questions

    public static function questions(): void
    {
        self::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $app = self::loadOwnDraftOrDeny($id);
        $questions = JobQuestion::forPosting((int) $app['job_posting_id']);

        if (!$questions) {
            header('Location: app.php?r=apply.review&id=' . $id);
            exit;
        }

        $existing = [];
        foreach (ApplicationAnswer::forApplication($id) as $a) {
            $existing[(int) $a['job_question_id']] = $a['answer_text'];
        }
        $main = View::render('careers/apply/questions', ['app' => $app, 'questions' => $questions, 'answers' => $existing]);
        View::careersShell('jobs', 'Apply — Questions', $main);
    }

    public static function questionsSave(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $id = (int) ($_POST['application_id'] ?? 0);
        $app = self::loadOwnDraftOrDeny($id);

        $questions = JobQuestion::forPosting((int) $app['job_posting_id']);
        $answers = [];
        $missing = false;
        foreach ($questions as $q) {
            $val = trim((string) ($_POST['q' . $q['id']] ?? ''));
            if ($q['is_required'] && $val === '') {
                $missing = true;
            }
            $answers[$q['id']] = $val;
        }
        if ($missing) {
            Session::flash('error', 'Please answer every required question.');
            header('Location: app.php?r=apply.questions&id=' . $id);
            exit;
        }
        ApplicationAnswer::saveAnswers($id, $answers);
        header('Location: app.php?r=apply.review&id=' . $id);
        exit;
    }

    // ------------------------------------------------------------------------ review

    public static function review(): void
    {
        self::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $app = self::loadOwnDraftOrDeny($id);
        $main = View::render('careers/apply/review', [
            'app' => $app,
            'documents' => JobApplicationDocument::forApplication($id),
            'answers' => ApplicationAnswer::forApplication($id),
            'profile' => JobApplicantProfile::forUser((int) Auth::id()),
        ]);
        View::careersShell('jobs', 'Apply — Review', $main);
    }

    /**
     * Validates the four things brief §46 requires before submission is allowed, then
     * submits. Nothing here is a soft warning — every failure blocks the submit.
     */
    public static function submit(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $id = (int) ($_POST['application_id'] ?? 0);
        $app = self::loadOwnDraftOrDeny($id);
        $userId = (int) Auth::id();

        $errors = [];

        $person = Database::one('SELECT address_line, city, state FROM user_profiles WHERE user_id = :u', ['u' => $userId]);
        $user = Database::one('SELECT phone FROM users WHERE id = :u', ['u' => $userId]);
        if (empty($user['phone']) || empty($person['address_line']) || empty($person['city']) || empty($person['state'])) {
            $errors[] = 'Complete your personal information before applying.';
        }

        $documents = JobApplicationDocument::forApplication($id);
        if (!array_filter($documents, static fn ($d) => $d['type'] === 'cv')) {
            $errors[] = 'Upload your CV/résumé before applying.';
        }

        $questions = JobQuestion::forPosting((int) $app['job_posting_id']);
        $answered = array_column(ApplicationAnswer::forApplication($id), 'answer_text', 'job_question_id');
        foreach ($questions as $q) {
            if ($q['is_required'] && empty($answered[$q['id']])) {
                $errors[] = 'Answer every required application question.';
                break;
            }
        }

        if (!isset($_POST['declaration'])) {
            $errors[] = 'You must accept the declaration to submit your application.';
        }
        if (!RateLimit::attempt('careers.apply', (string) $userId, 15, 3600)) {
            $errors[] = 'Too many submissions from your account this hour. Please try again later.';
        }
        if (!Captcha::verify()) {
            $errors[] = 'Please complete the verification challenge.';
        }

        if ($errors) {
            Session::flash('error', implode(' ', array_unique($errors)));
            header('Location: app.php?r=apply.review&id=' . $id);
            exit;
        }

        JobApplication::updateCoverLetter($id, trim((string) ($_POST['cover_letter'] ?? '')));
        JobApplication::submit($id);
        JobApplicationStatusHistory::record($id, 'draft', 'submitted', $userId);
        Role::grantByCode($userId, 'job_applicant');

        $submitted = JobApplication::find($id);
        Audit::log('job_application.submitted', 'job_applications', $id, null, [
            'job_posting_id' => $app['job_posting_id'], 'reference' => $submitted['reference'],
        ]);

        $mail = EmailTemplate::render('application_received', JobApplication::emailVars($submitted));
        Notify::send($userId, 'job_application.submitted', 'recruitment', $mail['subject'], $mail['body'], 'app.php?r=applications.show&id=' . $id);
        RecruitmentEmailLog::record($id, $submitted['email'], $mail['subject'], 'application_received', 'job_application.submitted');

        Session::flash('success', "Application {$submitted['reference']} submitted.");
        header('Location: app.php?r=applications.show&id=' . $id);
        exit;
    }

    // ----------------------------------------------------------------------- tracker

    public static function mine(): void
    {
        self::requireLogin();
        $main = View::render('careers/applications/index', ['applications' => JobApplication::forUser((int) Auth::id())]);
        View::careersShell('applications', 'My Applications', $main);
    }

    public static function show(): void
    {
        self::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $app = JobApplication::find($id);
        if (!$app || (int) $app['user_id'] !== (int) Auth::id()) {
            http_response_code(404);
            echo 'Application not found.';
            return;
        }
        $main = View::render('careers/applications/show', [
            'app' => $app,
            // Interview::forApplication() returns only scheduling fields — no feedback,
            // no panel notes, nothing internal-only (brief §23) leaks through this call.
            'interviews' => Interview::forApplication($id),
        ]);
        View::careersShell('applications', $app['reference'], $main);
    }

    public static function withdraw(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        $app = JobApplication::find($id);
        $userId = (int) Auth::id();
        if (!$app || (int) $app['user_id'] !== $userId) {
            http_response_code(404);
            echo 'Application not found.';
            return;
        }
        if (!in_array($app['status'], JobApplication::OPEN_STATUSES, true)) {
            Session::flash('error', 'That application can no longer be withdrawn.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }
        JobApplication::withdraw($id);
        JobApplicationStatusHistory::record($id, $app['status'], 'withdrawn', $userId);
        JobApplication::syncApplicantRole($userId);
        Audit::log('job_application.withdrawn', 'job_applications', $id, ['status' => $app['status']], ['status' => 'withdrawn']);

        $withdrawn = JobApplication::find($id);
        $mail = EmailTemplate::render('application_withdrawn', JobApplication::emailVars($withdrawn));
        Notify::send($userId, 'job_application.withdrawn', 'recruitment', $mail['subject'], $mail['body'], 'app.php?r=applications.show&id=' . $id);
        RecruitmentEmailLog::record($id, $withdrawn['email'], $mail['subject'], 'application_withdrawn', 'job_application.withdrawn');

        Session::flash('success', 'Application withdrawn.');
        header('Location: app.php?r=applications');
        exit;
    }
}
