<?php
declare(strict_types=1);

final class StaffController
{
    public static function index(): void
    {
        Auth::requirePermission('staff.member.view_any');
        $scope = Auth::scopeCentres('staff.member.view_any');
        $main = View::render('staff/index', ['staff' => StaffCentre::allWithCentre($scope)]);
        View::shell('staff', 'Staff', $main);
    }

    public static function assign(): void
    {
        Auth::requirePermission('staff.member.assign_centre');
        Csrf::requireValid();

        $email = trim((string) ($_POST['email'] ?? ''));
        $centreId = (int) ($_POST['centre_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $isPrimary = isset($_POST['is_primary']);

        $user = User::findByEmail($email);
        if (!$user) {
            Session::flash('error', "No account found for $email — the person needs to register first.");
            header('Location: app.php?r=centres.show&id=' . $centreId);
            exit;
        }

        Database::query('INSERT IGNORE INTO user_roles (user_id, role_id, centre_id, granted_by) VALUES (:u,:r,:c,:by)', [
            'u' => $user['id'], 'r' => $roleId, 'c' => $centreId, 'by' => Auth::id(),
        ]);
        StaffCentre::assign((int) $user['id'], $centreId, $isPrimary);

        Audit::log('staff.assigned', 'staff_centres', $centreId, null, ['user_id' => $user['id'], 'role_id' => $roleId]);
        Session::flash('success', "$email assigned to this centre.");
        header('Location: app.php?r=centres.show&id=' . $centreId);
        exit;
    }

    public static function users(): void
    {
        Auth::requirePermission('identity.user.view_any');
        $main = View::render('users/index', [
            'users' => User::allWithRoles(Auth::scopeCentres('identity.user.view_any')),
            'canCreate' => Auth::can('identity.user.create'),
            'roles' => Role::all(),
            'centres' => Centre::all(),
        ]);
        View::shell('users', 'Users', $main);
    }

    public static function storeUser(): void
    {
        Auth::requirePermission('identity.user.create');
        Csrf::requireValid();

        $email = trim((string) ($_POST['email'] ?? ''));
        if (User::findByEmail($email)) {
            Session::flash('error', 'That email is already registered.');
            header('Location: app.php?r=users');
            exit;
        }
        $roleId = (int) $_POST['role_id'];
        $role = Role::find($roleId);
        $centreId = ($role && $role['is_scopable'] && $_POST['centre_id'] !== '') ? (int) $_POST['centre_id'] : null;

        $id = User::create(
            $email,
            (string) $_POST['password'],
            trim((string) $_POST['first_name']),
            trim((string) $_POST['last_name']),
            $roleId,
            $centreId
        );
        if ($centreId !== null) {
            StaffCentre::assign($id, $centreId);
        }
        Audit::log('user.created', 'users', $id, null, ['email' => $email, 'role_id' => $roleId]);
        Session::flash('success', "User $email created.");
        header('Location: app.php?r=users');
        exit;
    }

    public static function userStatus(): void
    {
        Auth::requirePermission('identity.user.suspend');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $status = $_POST['status'];
        User::setStatus($id, $status);
        Audit::log('user.status_changed', 'users', $id, null, ['status' => $status]);
        Session::flash('success', 'User status updated.');
        header('Location: app.php?r=users');
        exit;
    }
}
