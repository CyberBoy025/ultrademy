<?php
declare(strict_types=1);

final class DashboardController
{
    public static function index(): void
    {
        $stats = [
            'users'      => Auth::can('identity.user.view_any') ? User::count() : null,
            'centres'    => Auth::can('staff.member.view_any') ? count(Centre::all()) : null,
            'programmes' => Programme::countPublished(),
            'enrolled'   => Auth::can('operations.attendance.view_any') ? Enrolment::count() : null,
        ];

        $main = View::render('dashboard/index', [
            'stats' => $stats,
            'myClassGroups' => Auth::hasRole('instructor') ? ClassGroup::forInstructor(Auth::id()) : [],
            'myEnrolments'  => Enrolment::forUser(Auth::id()),
        ]);

        View::shell('dashboard', 'Dashboard', $main);
    }
}
