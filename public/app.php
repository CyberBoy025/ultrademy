<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
Auth::requireLogin();

$route = (string) ($_GET['r'] ?? 'dashboard');

match ($route) {
    'dashboard' => DashboardController::index(),

    'programmes'         => ProgrammeController::index(),
    'programmes.store'   => ProgrammeController::store(),
    'programmes.show'    => ProgrammeController::show(),
    'programmes.status'  => ProgrammeController::status(),

    'centres'          => CentreController::index(),
    'centres.store'    => CentreController::store(),
    'centres.show'     => CentreController::show(),
    'centres.update'   => CentreController::update(),
    'rooms.store'      => CentreController::addRoom(),
    'rooms.status'     => CentreController::roomStatus(),
    'equipment.store'  => CentreController::addEquipment(),

    'staff'          => StaffController::index(),
    'staff.assign'   => StaffController::assign(),
    'users'          => StaffController::users(),
    'users.store'    => StaffController::storeUser(),
    'users.status'   => StaffController::userStatus(),

    'cohorts'           => OperationsController::cohorts(),
    'cohorts.store'     => OperationsController::storeCohort(),
    'cohorts.show'      => OperationsController::showCohort(),
    'cohorts.status'    => OperationsController::cohortStatus(),
    'classgroups.store' => OperationsController::storeClassGroup(),
    'sessions.store'    => OperationsController::storeSession(),

    'timetable' => OperationsController::timetable(),

    'attendance'       => OperationsController::attendanceIndex(),
    'attendance.mark'  => OperationsController::attendanceMark(),
    'attendance.save'  => OperationsController::attendanceSave(),

    'settings'        => PlatformController::settings(),
    'settings.update' => PlatformController::settingsUpdate(),
    'audit'           => PlatformController::audit(),

    default => (function () {
        http_response_code(404);
        echo '<p style="font:14px system-ui;padding:40px">Page not found. <a href="app.php">Back to dashboard</a></p>';
    })(),
};
