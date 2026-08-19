<?php
declare(strict_types=1);

/** Sidebar nav, filtered per request by what the signed-in user can actually do. */
final class Nav
{
    /** @return array<int,array{key:string,label:string,href:string,icon:string,badge?:int}> */
    public static function items(): array
    {
        $items = [];

        $items[] = [
            'key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'app.php?r=dashboard',
            'icon' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        ];

        // Everyone signed in can browse the programme catalogue (§57) — the controller
        // narrows to "published only" for anyone without education.programme.create.
        $items[] = [
            'key' => 'programmes', 'label' => 'Programmes', 'href' => 'app.php?r=programmes',
            'icon' => '<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>',
        ];

        // Entitlement-gated, not permission-gated: shown when the user's package (or their
        // staff implicit grant) includes `calendar`. The link disappearing is presentation
        // only — OperationsController::calendar() is the actual gate (04-subscriptions §6).
        if (Entitlements::can('calendar')) {
            $items[] = [
                'key' => 'calendar', 'label' => 'My Calendar', 'href' => 'app.php?r=calendar',
                'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            ];
        }

        if (Auth::can('staff.member.view_any')) {
            $items[] = [
                'key' => 'centres', 'label' => 'Centres', 'href' => 'app.php?r=centres',
                'icon' => '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/>',
            ];
        }

        if (Auth::can('operations.cohort.manage')) {
            $items[] = [
                'key' => 'cohorts', 'label' => 'Cohorts', 'href' => 'app.php?r=cohorts',
                'icon' => '<circle cx="9" cy="7" r="4"/><path d="M2 21c0-4.4 3.6-8 7-8s7 3.6 7 8"/><circle cx="17" cy="7" r="3"/><path d="M23 21c0-3.3-2-6-5-7.5"/>',
            ];
        }

        if (Auth::can('operations.session.schedule')) {
            $items[] = [
                'key' => 'timetable', 'label' => 'Timetable', 'href' => 'app.php?r=timetable',
                'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            ];
        }

        if (Auth::can('operations.attendance.mark') || Auth::can('operations.attendance.view_any')) {
            $items[] = [
                'key' => 'attendance', 'label' => 'Attendance', 'href' => 'app.php?r=attendance',
                'icon' => '<path d="M20 6 9 17l-5-5"/>',
            ];
        }

        if (Auth::can('staff.member.view_any')) {
            $items[] = [
                'key' => 'staff', 'label' => 'Staff', 'href' => 'app.php?r=staff',
                'icon' => '<circle cx="9" cy="7" r="4"/><path d="M2 21c0-4.4 3.6-8 7-8s7 3.6 7 8"/><circle cx="17" cy="7" r="3"/>',
            ];
        }

        if (Auth::can('identity.user.view_any')) {
            $items[] = [
                'key' => 'users', 'label' => 'Users', 'href' => 'app.php?r=users',
                'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
            ];
        }

        // Every signed-in user can see their own subscription and what it grants.
        $items[] = [
            'key' => 'subscription', 'label' => 'My Subscription', 'href' => 'app.php?r=subscription',
            'icon' => '<rect x="2" y="4" width="20" height="14" rx="2"/><path d="M2 8h20"/>',
        ];

        if (Auth::can('subscriptions.package.manage')) {
            $items[] = [
                'key' => 'packages', 'label' => 'Packages', 'href' => 'app.php?r=packages',
                'icon' => '<path d="m12 2 9 4.9V17L12 22l-9-5.1V6.9L12 2Z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
            ];
        }

        if (Auth::can('subscriptions.subscription.view_any')) {
            $items[] = [
                'key' => 'subscriptions', 'label' => 'Subscriptions', 'href' => 'app.php?r=subscriptions',
                'icon' => '<path d="M20 6 9 17l-5-5"/><rect x="3" y="4" width="18" height="16" rx="2"/>',
            ];
        }

        if (Auth::can('platform.audit.view')) {
            $items[] = [
                'key' => 'audit', 'label' => 'Audit Log', 'href' => 'app.php?r=audit',
                'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
            ];
        }

        if (Auth::can('platform.setting.update')) {
            $items[] = [
                'key' => 'settings', 'label' => 'Settings', 'href' => 'app.php?r=settings',
                'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2V21a2 2 0 11-4 0v-.1A1.7 1.7 0 007 19.4a1.7 1.7 0 00-1.9.4l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 003 14.1H3a2 2 0 110-4h.1"/>',
            ];
        }

        return $items;
    }
}
