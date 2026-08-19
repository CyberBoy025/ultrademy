<?php
declare(strict_types=1);

/** Enrolled students — the roster, status changes and centre transfers (README §14). */
final class StudentController
{
    public static function index(): void
    {
        Auth::requirePermission('admissions.enrolment.create');
        $scope = Auth::scopeCentres('admissions.enrolment.create');
        $status = (string) ($_GET['status'] ?? '');

        $main = View::render('students/index', [
            'students' => Enrolment::roster($scope, $status ?: null),
            'status' => $status,
            'canTransfer' => Auth::can('admissions.enrolment.transfer'),
            'showContact' => Auth::maySeeContactDetails(),
        ]);
        View::shell('students', 'Students', $main);
    }

    public static function show(): void
    {
        Auth::requirePermission('admissions.enrolment.create');
        $id = (int) ($_GET['id'] ?? 0);
        $enrolment = Enrolment::find($id);
        if (!$enrolment) {
            http_response_code(404);
            echo 'Enrolment not found.';
            return;
        }
        self::assertInScope($enrolment);

        $main = View::render('students/show', [
            'enrolment' => $enrolment,
            'canTransfer' => Auth::can('admissions.enrolment.transfer'),
            'showContact' => Auth::maySeeContactDetails(),
            'cohorts' => Cohort::forProgramme((int) $enrolment['programme_id']),
            'history' => Enrolment::forUser((int) $enrolment['user_id']),
        ]);
        View::shell('students', $enrolment['student_no'], $main);
    }

    /** The Phase 9 bridge: until invoices exist, admission → active is a manual step. */
    public static function setStatus(): void
    {
        Auth::requirePermission('admissions.enrolment.create');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $enrolment = Enrolment::find($id);
        if (!$enrolment) {
            http_response_code(404);
            echo 'Enrolment not found.';
            return;
        }
        self::assertInScope($enrolment);

        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['pending_payment', 'active', 'suspended', 'withdrawn', 'completed'], true)) {
            Session::flash('error', 'Invalid status.');
            header('Location: app.php?r=students.show&id=' . $id);
            exit;
        }

        Enrolment::setStatus($id, $status);
        Audit::log('enrolment.status_changed', 'enrolments', $id, ['status' => $enrolment['status']], ['status' => $status],
            $enrolment['centre_id'] ? (int) $enrolment['centre_id'] : null);
        Session::flash('success', 'Enrolment marked ' . str_replace('_', ' ', $status) . '.');
        header('Location: app.php?r=students.show&id=' . $id);
        exit;
    }

    public static function transfer(): void
    {
        Auth::requirePermission('admissions.enrolment.transfer');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $newCohortId = (int) $_POST['cohort_id'];

        $enrolment = Enrolment::find($id);
        if (!$enrolment) {
            http_response_code(404);
            echo 'Enrolment not found.';
            return;
        }
        if ((int) $enrolment['cohort_id'] === $newCohortId) {
            Session::flash('error', 'That is already their cohort.');
            header('Location: app.php?r=students.show&id=' . $id);
            exit;
        }

        $newId = Enrolment::transfer($id, $newCohortId);
        Audit::log('enrolment.transferred', 'enrolments', $id, ['cohort_id' => $enrolment['cohort_id']], [
            'new_enrolment_id' => $newId, 'cohort_id' => $newCohortId,
        ]);
        Session::flash('success', 'Transferred. The previous enrolment is kept as history.');
        header('Location: app.php?r=students.show&id=' . $newId);
        exit;
    }

    private static function assertInScope(array $enrolment): void
    {
        $scope = Auth::scopeCentres('admissions.enrolment.create');
        if ($scope === null) {
            return;
        }
        $centreId = $enrolment['centre_id'] !== null ? (int) $enrolment['centre_id'] : null;
        if ($centreId === null || !in_array($centreId, $scope, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
    }
}
