<?php
declare(strict_types=1);

final class CentreController
{
    public static function index(): void
    {
        Auth::requirePermission('staff.member.view_any');
        $scope = Auth::scopeCentres('staff.member.view_any');
        $centres = Centre::all($scope);
        foreach ($centres as &$c) {
            $c['counts'] = Centre::counts((int) $c['id']);
        }

        $main = View::render('centres/index', [
            'centres' => $centres,
            'canCreate' => Auth::can('staff.member.assign_centre'),
        ]);
        View::shell('centres', 'Centres', $main);
    }

    public static function store(): void
    {
        Auth::requirePermission('staff.member.assign_centre');
        Csrf::requireValid();
        $name = trim((string) ($_POST['name'] ?? ''));
        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        if ($name === '' || $code === '') {
            Session::flash('error', 'Name and code are required.');
            header('Location: app.php?r=centres');
            exit;
        }
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        $id = Centre::create($code, $name, $slug, trim((string) ($_POST['city'] ?? '')), trim((string) ($_POST['state'] ?? 'FCT')), 'planned');
        Audit::log('centre.created', 'centres', $id, null, ['name' => $name, 'code' => $code]);
        Session::flash('success', "Centre \"$name\" created.");
        header('Location: app.php?r=centres.show&id=' . $id);
        exit;
    }

    public static function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $centre = Centre::find($id);
        if (!$centre) {
            http_response_code(404);
            echo 'Centre not found.';
            return;
        }
        $scope = Auth::scopeCentres('staff.member.view_any');
        if ($scope !== null && !in_array($id, $scope, true)) {
            Auth::requirePermission('__denied__'); // always false -> 403
        }

        $main = View::render('centres/show', [
            'centre' => $centre,
            'counts' => Centre::counts($id),
            'rooms' => Room::allForCentre($id),
            'equipment' => Equipment::allForCentre($id),
            'staff' => StaffCentre::allForCentre($id),
            'canManage' => Auth::can('operations.room.manage'),
            'canAssignStaff' => Auth::can('staff.member.assign_centre'),
            'roles' => array_values(array_filter(Role::all(), fn($r) => (bool) $r['is_scopable'])),
        ]);
        View::shell('centres', $centre['name'], $main);
    }

    public static function update(): void
    {
        Auth::requirePermission('staff.member.assign_centre');
        Csrf::requireValid();
        $id = (int) ($_POST['id'] ?? 0);
        Centre::update(
            $id,
            trim((string) $_POST['name']),
            trim((string) $_POST['city']),
            trim((string) $_POST['state']),
            trim((string) $_POST['phone']),
            trim((string) $_POST['email']),
            $_POST['status']
        );
        Audit::log('centre.updated', 'centres', $id, null, $_POST);
        Session::flash('success', 'Centre updated.');
        header('Location: app.php?r=centres.show&id=' . $id);
        exit;
    }

    public static function addRoom(): void
    {
        Auth::requirePermission('operations.room.manage');
        Csrf::requireValid();
        $centreId = (int) $_POST['centre_id'];
        self::assertCentreInScope($centreId, 'operations.room.manage');
        $id = Room::create($centreId, trim((string) $_POST['name']), $_POST['type'], (int) ($_POST['capacity'] ?? 0));
        Audit::log('room.created', 'rooms', $id, null, ['centre_id' => $centreId]);
        Session::flash('success', 'Room added.');
        header('Location: app.php?r=centres.show&id=' . $centreId);
        exit;
    }

    public static function roomStatus(): void
    {
        Auth::requirePermission('operations.room.manage');
        Csrf::requireValid();
        $room = Room::find((int) $_POST['id']);
        if (!$room) {
            http_response_code(404);
            echo 'Room not found.';
            return;
        }
        // The room's own centre_id, not the posted one — the redirect target and the
        // authorization target must never be the same trusted-from-request value.
        self::assertCentreInScope((int) $room['centre_id'], 'operations.room.manage');
        Room::setStatus((int) $_POST['id'], $_POST['status']);
        Session::flash('success', 'Room status updated.');
        header('Location: app.php?r=centres.show&id=' . (int) $room['centre_id']);
        exit;
    }

    public static function addEquipment(): void
    {
        Auth::requirePermission('operations.equipment.manage');
        Csrf::requireValid();
        $centreId = (int) $_POST['centre_id'];
        self::assertCentreInScope($centreId, 'operations.equipment.manage');
        $roomId = $_POST['room_id'] !== '' ? (int) $_POST['room_id'] : null;
        $id = Equipment::create($centreId, $roomId, trim((string) $_POST['asset_tag']), trim((string) $_POST['name']));
        Audit::log('equipment.created', 'equipment', $id, null, ['centre_id' => $centreId]);
        Session::flash('success', 'Equipment added.');
        header('Location: app.php?r=centres.show&id=' . $centreId);
        exit;
    }

    /**
     * The same check show() already applies via its `__denied__` trick, factored out so
     * the write actions (add/edit a room or equipment) enforce it too — a scoped
     * centre_manager must not be able to add a room to a centre they do not manage just
     * because the form posts a centre_id of its own choosing.
     */
    private static function assertCentreInScope(int $centreId, string $permission): void
    {
        $scope = Auth::scopeCentres($permission);
        if ($scope !== null && !in_array($centreId, $scope, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
    }
}
