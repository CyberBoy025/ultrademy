<?php
declare(strict_types=1);

final class OperationsController
{
    public static function cohorts(): void
    {
        Auth::requirePermission('operations.cohort.manage');
        $scope = Auth::scopeCentres('operations.cohort.manage');
        $main = View::render('cohorts/index', ['cohorts' => Cohort::all($scope)]);
        View::shell('cohorts', 'Cohorts', $main);
    }

    public static function storeCohort(): void
    {
        Auth::requirePermission('operations.cohort.manage');
        Csrf::requireValid();
        $programmeId = (int) $_POST['programme_id'];
        $name = trim((string) $_POST['name']);
        $code = 'COH-' . strtoupper(bin2hex(random_bytes(3)));
        $centreId = $_POST['centre_id'] !== '' ? (int) $_POST['centre_id'] : null;

        $id = Cohort::create($programmeId, $centreId, $code, $name, $_POST['starts_on'] ?: null, $_POST['ends_on'] ?: null, null);
        Audit::log('cohort.created', 'cohorts', $id, null, ['name' => $name, 'programme_id' => $programmeId]);
        Session::flash('success', "Cohort \"$name\" created.");
        header('Location: app.php?r=programmes.show&id=' . $programmeId);
        exit;
    }

    public static function showCohort(): void
    {
        Auth::requirePermission('operations.cohort.manage');
        $id = (int) ($_GET['id'] ?? 0);
        $cohort = Cohort::find($id);
        if (!$cohort) {
            http_response_code(404);
            echo 'Cohort not found.';
            return;
        }
        $groups = ClassGroup::forCohort($id);
        foreach ($groups as &$g) {
            $g['sessions'] = ClassSession::forGroup((int) $g['id']);
        }
        $main = View::render('cohorts/show', [
            'cohort' => $cohort,
            'groups' => $groups,
            'enrolments' => Enrolment::forCohort($id),
            'instructors' => self::instructorOptions(),
            'canSchedule' => Auth::can('operations.session.schedule'),
            'rooms' => $cohort['centre_id'] ? Room::allForCentre((int) $cohort['centre_id']) : [],
        ]);
        View::shell('cohorts', $cohort['name'], $main);
    }

    public static function cohortStatus(): void
    {
        Auth::requirePermission('operations.cohort.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        Cohort::setStatus($id, $_POST['status']);
        Audit::log('cohort.status_changed', 'cohorts', $id, null, ['status' => $_POST['status']]);
        Session::flash('success', 'Cohort status updated.');
        header('Location: app.php?r=cohorts.show&id=' . $id);
        exit;
    }

    public static function storeClassGroup(): void
    {
        Auth::requirePermission('operations.cohort.manage');
        Csrf::requireValid();
        $cohortId = (int) $_POST['cohort_id'];
        $instructorId = $_POST['instructor_user_id'] !== '' ? (int) $_POST['instructor_user_id'] : null;
        $id = ClassGroup::create($cohortId, $instructorId, trim((string) $_POST['name']), $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null);
        Audit::log('class_group.created', 'class_groups', $id, null, ['cohort_id' => $cohortId]);
        Session::flash('success', 'Class group added.');
        header('Location: app.php?r=cohorts.show&id=' . $cohortId);
        exit;
    }

    public static function storeSession(): void
    {
        Auth::requirePermission('operations.session.schedule');
        Csrf::requireValid();
        $groupId = (int) $_POST['class_group_id'];
        $roomId = $_POST['room_id'] !== '' ? (int) $_POST['room_id'] : null;
        $mode = $roomId === null ? 'online' : 'physical';
        $id = ClassSession::create($groupId, $roomId, trim((string) $_POST['topic']), $_POST['starts_at'], $_POST['ends_at'], $mode);
        Audit::log('class_session.created', 'class_sessions', $id, null, ['class_group_id' => $groupId]);
        Session::flash('success', 'Session scheduled.');

        $group = ClassGroup::find($groupId);
        header('Location: app.php?r=cohorts.show&id=' . ($group['cohort_id'] ?? ''));
        exit;
    }

    /**
     * The user's own schedule. This is the first real ENTITLEMENT gate in the codebase:
     * `calendar` is sold in Standard and above (04-subscriptions §4), while staff get it
     * implicitly because an instructor should not have to buy a package to see the class
     * they are teaching (§5 step 3 / Decision 16).
     *
     * Failure here is 402, not 403 — the user is allowed to want this, they just have not
     * bought it.
     */
    public static function calendar(): void
    {
        Entitlements::requireFeature('calendar');
        $main = View::render('calendar/index', [
            'sessions' => ClassSession::forUser((int) Auth::id()),
        ]);
        View::shell('calendar', 'My Calendar', $main);
    }

    public static function timetable(): void
    {
        Auth::requirePermission('operations.session.schedule');
        $scope = Auth::scopeCentres('operations.session.schedule');
        $main = View::render('timetable/index', ['sessions' => ClassSession::upcoming($scope)]);
        View::shell('timetable', 'Timetable', $main);
    }

    public static function attendanceIndex(): void
    {
        $canMark = Auth::can('operations.attendance.mark');
        if (!$canMark && !Auth::can('operations.attendance.view_any')) {
            Auth::requirePermission('operations.attendance.mark');
        }

        // 03-rbac.md §5 grades this permission `◐` for staff (centre-scoped) but `○` for
        // students — own records only. scopeCentres() models GLOBAL vs CENTRES and has no
        // notion of ownership, so a student's global grant would otherwise resolve to
        // GLOBAL and expose every centre's timetable. Ownership scope is applied here
        // instead, per §7: "a student's queries are constrained to user_id = self".
        if ($canMark || Auth::isStaff()) {
            $scope = $canMark
                ? Auth::scopeCentres('operations.attendance.mark')
                : Auth::scopeCentres('operations.attendance.view_any');
            $sessions = ClassSession::upcoming($scope, 30);
        } else {
            $sessions = ClassSession::forUser((int) Auth::id(), 30);
        }

        foreach ($sessions as &$s) {
            $s['rate'] = AttendanceRecord::rateForSession((int) $s['id']);
        }
        $main = View::render('attendance/index', ['sessions' => $sessions, 'canMark' => $canMark]);
        View::shell('attendance', 'Attendance', $main);
    }

    public static function attendanceMark(): void
    {
        Auth::requirePermission('operations.attendance.mark');
        $sessionId = (int) ($_GET['session_id'] ?? 0);
        $session = ClassSession::find($sessionId);
        if (!$session) {
            http_response_code(404);
            echo 'Session not found.';
            return;
        }
        // Enrolments in this session's cohort.
        $cohortRow = Database::one('SELECT co.id FROM class_groups cg JOIN cohorts co ON co.id = cg.cohort_id WHERE cg.id = :g', ['g' => $session['class_group_id']]);
        $enrolments = $cohortRow ? Enrolment::forCohort((int) $cohortRow['id']) : [];
        $existing = AttendanceRecord::forSession($sessionId);

        $main = View::render('attendance/mark', ['session' => $session, 'enrolments' => $enrolments, 'existing' => $existing]);
        View::shell('attendance', 'Mark Attendance', $main);
    }

    public static function attendanceSave(): void
    {
        Auth::requirePermission('operations.attendance.mark');
        Csrf::requireValid();
        $sessionId = (int) $_POST['session_id'];
        $marks = $_POST['status'] ?? [];
        $count = 0;
        foreach ($marks as $enrolmentId => $status) {
            if (in_array($status, ['present', 'late', 'absent', 'excused'], true)) {
                AttendanceRecord::mark($sessionId, (int) $enrolmentId, $status);
                $count++;
            }
        }
        Audit::log('attendance.marked', 'class_sessions', $sessionId, null, ['records' => $count]);
        Session::flash('success', "Attendance saved for $count student(s).");
        header('Location: app.php?r=attendance');
        exit;
    }

    private static function instructorOptions(): array
    {
        return Database::all(
            "SELECT u.id, CONCAT(p.first_name, ' ', p.last_name) AS name FROM users u
             JOIN user_profiles p ON p.user_id = u.id
             JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
             WHERE r.code = 'instructor' GROUP BY u.id ORDER BY name"
        );
    }
}
