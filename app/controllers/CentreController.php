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
        Room::setStatus((int) $_POST['id'], $_POST['status']);
        Session::flash('success', 'Room status updated.');
        header('Location: app.php?r=centres.show&id=' . (int) $_POST['centre_id']);
        exit;
    }

    public static function addEquipment(): void
    {
        Auth::requirePermission('operations.equipment.manage');
        Csrf::requireValid();
        $centreId = (int) $_POST['centre_id'];
        $roomId = $_POST['room_id'] !== '' ? (int) $_POST['room_id'] : null;
        $id = Equipment::create($centreId, $roomId, trim((string) $_POST['asset_tag']), trim((string) $_POST['name']));
        Audit::log('equipment.created', 'equipment', $id, null, ['centre_id' => $centreId]);
        Session::flash('success', 'Equipment added.');
        header('Location: app.php?r=centres.show&id=' . $centreId);
        exit;
    }
}
