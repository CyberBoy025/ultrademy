<?php
declare(strict_types=1);

/**
 * Programme applications — README §10, §11, §34.
 *
 * ON THE ENTITLEMENT METER
 * ------------------------
 * `programme_applications` is a metered feature (04-subscriptions §4: Standard 1,
 * Premium 3, Advanced unlimited, Basic none). Applying that literally to every route
 * would mean a person with no subscription can never apply for anything — which
 * contradicts 04-subscriptions §8, whose Decision 3 default states plainly that
 * enrolling in a programme does NOT require a subscription because "physical training is
 * sold on its own".
 *
 * The two are reconciled by metering the SELF-SERVICE channel only:
 *
 *   - a user applying online for themselves      → metered, 402 when over the limit
 *   - staff recording an application for someone → permission-gated only
 *   - staff enrolling a walk-in directly         → permission-gated only
 *
 * That is consistent with `enrolments.application_id` being nullable "direct enrolment
 * possible" (02-data-model.md §4), and it keeps the hub counter working for someone who
 * pays cash at Gwagwalada. Flagged in 12-applications-students.md §7 as worth
 * confirming, since neither document states it outright.
 */
final class ApplicationController
{
    // ---------------------------------------------------------------- applicant side

    public static function apply(): void
    {
        $userId = (int) Auth::id();
        $main = View::render('applications/apply', [
            'programmes' => Programme::all(true),
            'centres' => Centre::all(),
            'openCount' => Application::countOpenForUser($userId),
            'limit' => Entitlements::limitFor('programme_applications'),
            'entitled' => Entitlements::can('programme_applications'),
        ]);
        View::shell('myapplications', 'Apply', $main);
    }

    public static function store(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();

        // Self-service only — see the class docblock.
        Entitlements::requireWithinLimit('programme_applications', Application::countOpenForUser($userId));

        $programmeId = (int) ($_POST['programme_id'] ?? 0);
        $programme = Programme::find($programmeId);
        if (!$programme || $programme['status'] !== 'published') {
            Session::flash('error', 'That programme is not open for applications.');
            header('Location: app.php?r=apply');
            exit;
        }

        $centreId = ($_POST['preferred_centre_id'] ?? '') !== '' ? (int) $_POST['preferred_centre_id'] : null;
        if ($programme['delivery_mode'] === 'online') {
            $centreId = null; // an online programme has no preferred centre
        }

        $id = Application::create($userId, $programmeId, $centreId, trim((string) ($_POST['motivation'] ?? '')));
        Application::submit($id);

        $app = Application::find($id);
        Audit::log('application.submitted', 'applications', $id, null, [
            'programme_id' => $programmeId, 'reference' => $app['reference'],
        ], $centreId);

        // §37 trigger table: the applicant, and the manager of the centre they chose.
        Notify::send($userId, 'application.submitted', 'admission',
            'Application ' . $app['reference'] . ' submitted',
            'We have your application for ' . $app['programme_title'] . '. You will hear from us once it is reviewed.',
            'app.php?r=applications.show&id=' . $id);

        if ($centreId !== null) {
            $managers = Database::all(
                'SELECT manager_user_id FROM centres WHERE id = :c AND manager_user_id IS NOT NULL',
                ['c' => $centreId]
            );
            Notify::sendMany(
                array_map('intval', array_column($managers, 'manager_user_id')),
                'application.received', 'admission',
                'New application for ' . $app['programme_title'],
                ($app['applicant_name'] ?: $app['email']) . ' applied to your centre.',
                'app.php?r=applications.show&id=' . $id
            );
        }

        Session::flash('success', "Application {$app['reference']} submitted.");
        header('Location: app.php?r=applications.show&id=' . $id);
        exit;
    }

    public static function mine(): void
    {
        $userId = (int) Auth::id();
        $main = View::render('applications/mine', [
            'applications' => Application::forUser($userId),
            'enrolments' => Enrolment::forUser($userId),
        ]);
        View::shell('myapplications', 'My Applications', $main);
    }

    public static function withdraw(): void
    {
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $app = Application::find($id);
        if (!$app || (int) $app['user_id'] !== (int) Auth::id()) {
            http_response_code(403);
            echo 'Not your application.';
            return;
        }
        if (!in_array($app['status'], Application::OPEN_STATUSES, true)) {
            Session::flash('error', 'That application can no longer be withdrawn.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }
        Application::withdraw($id);
        Application::syncApplicantRole((int) $app['user_id']);
        Audit::log('application.withdrawn', 'applications', $id, ['status' => $app['status']], ['status' => 'withdrawn']);
        Session::flash('success', 'Application withdrawn.');
        header('Location: app.php?r=myapplications');
        exit;
    }

    // ------------------------------------------------------------------- staff side

    public static function index(): void
    {
        Auth::requirePermission('admissions.application.view_any');
        $scope = Auth::scopeCentres('admissions.application.view_any');
        $status = (string) ($_GET['status'] ?? '');

        $main = View::render('applications/index', [
            'applications' => Application::queue($scope, $status ?: null),
            'counts' => Application::statusCounts($scope),
            'status' => $status,
        ]);
        View::shell('applications', 'Applications', $main);
    }

    public static function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $app = Application::find($id);
        if (!$app) {
            http_response_code(404);
            echo 'Application not found.';
            return;
        }
        if (!self::mayView($app)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }

        $isOwner = (int) $app['user_id'] === (int) Auth::id();
        $main = View::render('applications/show', [
            'app' => $app,
            'documents' => ApplicationDocument::forApplication($id),
            'isOwner' => $isOwner,
            'canReview' => Auth::can('admissions.application.review'),
            'canApprove' => Auth::can('admissions.application.approve'),
            'canReject' => Auth::can('admissions.application.reject'),
            'canEnrol' => Auth::can('admissions.enrolment.create'),
            'cohorts' => Cohort::forProgramme((int) $app['programme_id']),
            'enrolment' => Database::one('SELECT id, student_no, status FROM enrolments WHERE application_id = :a', ['a' => $id]),
        ]);
        View::shell($isOwner && !Auth::can('admissions.application.review') ? 'myapplications' : 'applications', $app['reference'], $main);
    }

    /** Moves submitted → under_review. Deliberately separate from deciding it. */
    public static function review(): void
    {
        Auth::requirePermission('admissions.application.review');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $app = self::loadInScopeOrDeny($id, 'admissions.application.review');

        Application::setStatus($id, 'under_review');
        Audit::log('application.under_review', 'applications', $id, ['status' => $app['status']], ['status' => 'under_review'], $app['preferred_centre_id'] ? (int) $app['preferred_centre_id'] : null);
        Session::flash('success', 'Marked as under review.');
        header('Location: app.php?r=applications.show&id=' . $id);
        exit;
    }

    /**
     * Approve or reject. `approve` and `reject` are separate permissions from `review`
     * (03-rbac.md §3: "approve is never implied by update"), which is what implements
     * Decision 9 — a centre manager reviews and recommends, management decides.
     */
    public static function decide(): void
    {
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $decision = (string) ($_POST['decision'] ?? '');

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            Session::flash('error', 'Invalid decision.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }
        Auth::requirePermission($decision === 'approved' ? 'admissions.application.approve' : 'admissions.application.reject');
        $app = self::loadInScopeOrDeny($id, $decision === 'approved' ? 'admissions.application.approve' : 'admissions.application.reject');

        $note = trim((string) ($_POST['decision_note'] ?? ''));
        $cohortId = ($_POST['cohort_id'] ?? '') !== '' ? (int) $_POST['cohort_id'] : null;

        if ($decision === 'approved' && $cohortId === null) {
            Session::flash('error', 'Assign a cohort before approving — README §34 requires assignment as part of admission.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }

        Application::setStatus($id, $decision, $note !== '' ? $note : null, $cohortId);
        if ($decision === 'rejected') {
            Application::syncApplicantRole((int) $app['user_id']);
        }
        Audit::log("application.$decision", 'applications', $id, ['status' => $app['status']], [
            'status' => $decision, 'cohort_id' => $cohortId,
        ], $app['preferred_centre_id'] ? (int) $app['preferred_centre_id'] : null);

        // `admission` is a locked category — an applicant can never switch off the
        // message that tells them the outcome.
        Notify::send((int) $app['user_id'], 'application.' . $decision, 'admission',
            $decision === 'approved'
                ? 'Your application was approved'
                : 'Your application was not successful',
            $decision === 'approved'
                ? 'Congratulations — your application for ' . $app['programme_title'] . ' has been approved.'
                : ($note !== '' ? $note : 'Thank you for applying for ' . $app['programme_title'] . '.'),
            'app.php?r=applications.show&id=' . $id);

        Session::flash('success', $decision === 'approved' ? 'Approved. You can now admit the applicant.' : 'Application rejected.');
        header('Location: app.php?r=applications.show&id=' . $id);
        exit;
    }

    /** Turns an approved application into an enrolment — the applicant becomes a student. */
    public static function enrol(): void
    {
        Auth::requirePermission('admissions.enrolment.create');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $app = self::loadInScopeOrDeny($id, 'admissions.enrolment.create');

        if ($app['status'] !== 'approved') {
            Session::flash('error', 'Only an approved application can be admitted.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }
        if ($app['cohort_id'] === null) {
            Session::flash('error', 'This application has no cohort assigned.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }
        if (Database::one('SELECT 1 FROM enrolments WHERE application_id = :a', ['a' => $id])) {
            Session::flash('error', 'This application has already been admitted.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }

        $enrolmentId = Enrolment::create((int) $app['user_id'], (int) $app['cohort_id'], $id);
        Application::syncApplicantRole((int) $app['user_id']);

        $enrolment = Enrolment::find($enrolmentId);
        Audit::log('enrolment.created', 'enrolments', $enrolmentId, null, [
            'application_id' => $id, 'student_no' => $enrolment['student_no'],
        ], $enrolment['centre_id'] ? (int) $enrolment['centre_id'] : null);

        Notify::send((int) $app['user_id'], 'enrolment.created', 'admission',
            'You are enrolled — ' . $enrolment['student_no'],
            'You have been admitted to ' . $app['programme_title'] . '. Your place is held pending payment.',
            'app.php?r=myapplications');

        Session::flash('success', "Admitted as {$enrolment['student_no']} — awaiting payment.");
        header('Location: app.php?r=applications.show&id=' . $id);
        exit;
    }

    // -------------------------------------------------------------------- documents

    public static function uploadDocument(): void
    {
        Csrf::requireValid();
        $id = (int) ($_POST['application_id'] ?? 0);
        $app = Application::find($id);
        if (!$app || !self::mayView($app)) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }
        // Only the applicant, or staff acting for them, may add documents, and only while open.
        if (!in_array($app['status'], Application::OPEN_STATUSES, true)) {
            Session::flash('error', 'This application is closed — documents can no longer be added.');
            header('Location: app.php?r=applications.show&id=' . $id);
            exit;
        }

        $type = in_array($_POST['type'] ?? '', ['id_card', 'certificate', 'passport_photo', 'other'], true) ? $_POST['type'] : 'other';
        $error = ApplicationDocument::store($id, $type, $_FILES['document'] ?? []);

        if ($error !== null) {
            Session::flash('error', $error);
        } else {
            Audit::log('application.document_uploaded', 'applications', $id, null, ['type' => $type]);
            Session::flash('success', 'Document uploaded.');
        }
        header('Location: app.php?r=applications.show&id=' . $id);
        exit;
    }

    /**
     * Streams a document after an authorisation check.
     *
     * These files live outside the web root precisely so that this method is the ONLY
     * way to read them — there is no URL that bypasses it (README §42).
     */
    public static function downloadDocument(): void
    {
        $doc = ApplicationDocument::find((int) ($_GET['id'] ?? 0));
        if (!$doc) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        $app = Application::find((int) $doc['application_id']);
        if (!$app || !self::mayView($app)) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }

        $path = ApplicationDocument::storageDir() . '/' . $doc['stored_name'];
        if (!is_file($path)) {
            http_response_code(404);
            echo 'File missing from storage.';
            return;
        }

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Length: ' . filesize($path));
        // `attachment` + nosniff: never let an uploaded file execute or render inline.
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $doc['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public static function documentStatus(): void
    {
        Auth::requirePermission('admissions.application.review');
        Csrf::requireValid();
        $doc = ApplicationDocument::find((int) $_POST['id']);
        if (!$doc) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        $appId = (int) $doc['application_id'];
        self::loadInScopeOrDeny($appId, 'admissions.application.review');

        $status = in_array($_POST['status'] ?? '', ['pending', 'accepted', 'rejected'], true) ? $_POST['status'] : 'pending';
        ApplicationDocument::setStatus((int) $doc['id'], $status, trim((string) ($_POST['reviewer_note'] ?? '')) ?: null);
        Audit::log('application.document_reviewed', 'application_documents', (int) $doc['id'], null, ['status' => $status]);
        Session::flash('success', 'Document marked ' . $status . '.');
        header('Location: app.php?r=applications.show&id=' . $appId);
        exit;
    }

    // ---------------------------------------------------------------------- helpers

    /** Owner always; otherwise staff with view_any whose centre scope covers it. */
    private static function mayView(array $app): bool
    {
        if ((int) $app['user_id'] === (int) Auth::id()) {
            return true;
        }
        if (!Auth::can('admissions.application.view_any')) {
            return false;
        }
        $scope = Auth::scopeCentres('admissions.application.view_any');
        if ($scope === null) {
            return true; // global
        }
        $centreId = $app['preferred_centre_id'] !== null ? (int) $app['preferred_centre_id'] : null;
        return $centreId !== null && in_array($centreId, $scope, true);
    }

    /** @return array<string,mixed> the application, or exits 403/404. */
    private static function loadInScopeOrDeny(int $id, string $permission): array
    {
        $app = Application::find($id);
        if (!$app) {
            http_response_code(404);
            echo 'Application not found.';
            exit;
        }
        $scope = Auth::scopeCentres($permission);
        if ($scope !== null) {
            $centreId = $app['preferred_centre_id'] !== null ? (int) $app['preferred_centre_id'] : null;
            if ($centreId === null || !in_array($centreId, $scope, true)) {
                http_response_code(403);
                require dirname(__DIR__) . '/views/errors/403.php';
                exit;
            }
        }
        return $app;
    }
}
