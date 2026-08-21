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

        // Notifications are for everyone; the badge is the unread count.
        $unread = Notify::unreadCount();
        $notif = [
            'key' => 'notifications', 'label' => 'Notifications', 'href' => 'app.php?r=notifications',
            'icon' => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',
        ];
        if ($unread > 0) {
            $notif['badge'] = $unread;
        }
        $items[] = $notif;

        // Messaging is entitlement-gated — the link disappears when the package excludes
        // it. The controller is the real gate (04-subscriptions §6).
        if (Entitlements::can('chat_direct') || Entitlements::can('chat_groups')) {
            $items[] = [
                'key' => 'chat', 'label' => 'Messages', 'href' => 'app.php?r=chat',
                'icon' => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
            ];
        }

        if (Auth::can('comms.announcement.publish')) {
            $items[] = [
                'key' => 'announcements', 'label' => 'Announcements', 'href' => 'app.php?r=announcements',
                'icon' => '<path d="M3 11v2a1 1 0 001 1h3l4 4V6L7 10H4a1 1 0 00-1 1z"/><path d="M16 8a5 5 0 010 8"/>',
            ];
        }

        // Learners see My Learning once they have any course; staff manage the catalogue.
        if (!Auth::isStaff()) {
            $items[] = [
                'key' => 'learn', 'label' => 'My Learning', 'href' => 'app.php?r=learn',
                'icon' => '<path d="M4 5.5A1.5 1.5 0 015.5 4H11v16H5.5A1.5 1.5 0 014 18.5z"/><path d="M20 5.5A1.5 1.5 0 0018.5 4H13v16h5.5a1.5 1.5 0 001.5-1.5z"/>',
            ];
        }

        if (Auth::can('education.lesson.view')) {
            $items[] = [
                'key' => 'courses', 'label' => 'Courses', 'href' => 'app.php?r=courses',
                'icon' => '<path d="M4 5.5A1.5 1.5 0 015.5 4H11v16H5.5A1.5 1.5 0 014 18.5z"/><path d="M20 5.5A1.5 1.5 0 0018.5 4H13v16h5.5a1.5 1.5 0 001.5-1.5z"/>',
            ];
        }

        // Assessment marking sits beside assignment grading but is a distinct permission:
        // an organisation may want a senior examiner marking papers without also handing
        // them every assignment submission (086_assessment_permissions.sql).
        if (Auth::can('education.assessment.grade')) {
            $items[] = [
                'key' => 'assessmentmarking', 'label' => 'Assessment Marking', 'href' => 'app.php?r=assessments.marking',
                'icon' => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h9"/>',
            ];
        }

        if (Auth::can('education.assignment.grade')) {
            $items[] = [
                'key' => 'grading', 'label' => 'Grading', 'href' => 'app.php?r=grading',
                'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 15l2 2 4-4"/>',
            ];
        }

        // Applicants and students track their own applications; staff get the review queue
        // instead. Someone who is both sees both, which is the point of one account holding
        // several relationships (README §3).
        if (!Auth::isStaff()) {
            $items[] = [
                'key' => 'myapplications', 'label' => 'My Applications', 'href' => 'app.php?r=myapplications',
                'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
            ];
        }

        if (Auth::can('admissions.application.view_any') && Auth::isStaff()) {
            $items[] = [
                'key' => 'applications', 'label' => 'Applications', 'href' => 'app.php?r=applications',
                'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
            ];
        }

        // Recruitment — job.manage lands on Jobs, application.view_any-only (recruiter) lands
        // on the pipeline; RecruitmentAdminController::index() picks the right one.
        if (Auth::can('recruitment.job.manage') || Auth::can('recruitment.application.view_any')) {
            $items[] = [
                'key' => 'recruitment', 'label' => 'Recruitment', 'href' => 'app.php?r=recruitment',
                'icon' => '<path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
            ];
        }
        if (Auth::can('recruitment.email_template.manage')) {
            $items[] = [
                'key' => 'recruitmentemail', 'label' => 'Recruitment Email', 'href' => 'app.php?r=recruitment.emailtemplates',
                'icon' => '<path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/>',
            ];
        }
        if (Auth::can('recruitment.report.view')) {
            $items[] = [
                'key' => 'recruitmentreports', 'label' => 'Recruitment Reports', 'href' => 'app.php?r=recruitment.reports',
                'icon' => '<path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="8"/><rect x="13" y="6" width="3" height="12"/>',
            ];
        }
        if (Auth::can('recruitment.interview.feedback')) {
            $items[] = [
                'key' => 'recruitmentinterviews', 'label' => 'My Interviews', 'href' => 'app.php?r=recruitment.interviews.mine',
                'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            ];
        }

        if (Auth::can('admissions.enrolment.create')) {
            $items[] = [
                'key' => 'students', 'label' => 'Students', 'href' => 'app.php?r=students',
                'icon' => '<path d="M3 9l9-5 9 5-9 5-9-5z"/><path d="M7 11.5V16c0 1.7 2.2 3 5 3s5-1.3 5-3v-4.5"/>',
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

        // Payers see their own bill; finance sees the ledger.
        if (!Auth::can('finance.invoice.view_any')) {
            $items[] = [
                'key' => 'billing', 'label' => 'My Payments', 'href' => 'app.php?r=billing',
                'icon' => '<rect x="2" y="4" width="20" height="14" rx="2"/><path d="M2 8h20"/>',
            ];
        } else {
            $items[] = [
                'key' => 'invoices', 'label' => 'Invoices', 'href' => 'app.php?r=invoices',
                'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/>',
            ];
            $items[] = [
                'key' => 'payments', 'label' => 'Payments', 'href' => 'app.php?r=payments',
                'icon' => '<rect x="2" y="4" width="20" height="14" rx="2"/><path d="M2 8h20"/>',
            ];
        }

        if (Auth::can('finance.payment.verify')) {
            $items[] = [
                'key' => 'verify', 'label' => 'Verify Transfers', 'href' => 'app.php?r=verify',
                'icon' => '<path d="M20 6 9 17l-5-5"/><rect x="3" y="3" width="18" height="18" rx="2"/>',
            ];
        }

        if (Auth::can('finance.expense.record')) {
            $items[] = [
                'key' => 'expenses', 'label' => 'Expenses', 'href' => 'app.php?r=expenses',
                'icon' => '<path d="M3 3v18h18"/><path d="M7 15l4-5 4 3 5-7"/>',
            ];
        }

        if (Auth::can('finance.refund.create') || Auth::can('finance.refund.approve')) {
            $items[] = [
                'key' => 'refunds', 'label' => 'Refunds', 'href' => 'app.php?r=refunds',
                'icon' => '<path d="M3 12a9 9 0 109-9"/><path d="M3 3v6h6"/>',
            ];
        }

        // Entitlement-gated for learners, permission-gated for staff — the same feature
        // seen from two sides. The controller is the real gate either way.
        //
        // The affiliate self-service pages moved to their own portal (public/affiliate/),
        // its own front controller and its own session cookie, the same separation
        // careers has from the main app — so this is a cross-app link (affiliate_url()),
        // not an internal route, even though it is still the same account underneath.
        // Straight to r=dashboard, not the portal's own public home — someone clicking
        // this from inside their own sidebar already knows what the programme is.
        if (!Auth::isStaff() && Entitlements::can('affiliate_programme')) {
            $items[] = [
                'key' => 'affiliate', 'label' => 'Affiliate', 'href' => affiliate_url('app.php?r=dashboard'),
                'icon' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
            ];
        }
        if (Auth::can('affiliate.referral.view_any')) {
            $items[] = [
                'key' => 'affiliateadmin', 'label' => 'Affiliates', 'href' => 'app.php?r=affiliate.admin',
                'icon' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
            ];
        }
        if (Auth::can('affiliate.commission.approve')) {
            $items[] = [
                'key' => 'affiliatecommissions', 'label' => 'Commissions', 'href' => 'app.php?r=affiliate.commissions',
                'icon' => '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',
            ];
        }

        // Sits high in the list: for management this is the landing page, and for a
        // centre manager it is the operational summary of their own centre.
        if (Auth::can('corporate.request.manage') || Auth::can('corporate.report.view')) {
            $items[] = [
                'key' => 'corporate', 'label' => 'Corporate',
                'href' => Auth::can('corporate.request.manage') ? 'app.php?r=corporate' : 'app.php?r=corporate.contracts',
                'icon' => '<path d="M3 21h18M5 21V7l7-4v18M19 21V11l-7-4"/><path d="M9 9h.01M9 13h.01M9 17h.01M15 13h.01M15 17h.01"/>',
            ];
        }

        if (Auth::can('management.report.view')) {
            $items[] = [
                'key' => 'management', 'label' => 'Management', 'href' => 'app.php?r=management',
                'icon' => '<path d="M3 3v18h18"/><path d="M7 15l4-5 4 3 5-7"/><circle cx="11" cy="10" r="1.5"/>',
            ];
        }

        if (Auth::can('donation.view_any') || Auth::can('donation.campaign.manage')) {
            $items[] = [
                'key' => 'donations', 'label' => 'Donations',
                'href' => Auth::can('donation.view_any') ? 'app.php?r=donations' : 'app.php?r=donations.campaigns',
                'icon' => '<path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l8.8 8.8 8.8-8.8a5.5 5.5 0 000-7.8z"/>',
            ];
        }

        if (Auth::can('finance.report.view')) {
            $items[] = [
                'key' => 'reports', 'label' => 'Financial Reports', 'href' => 'app.php?r=reports',
                'icon' => '<path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="8"/><rect x="13" y="6" width="3" height="12"/>',
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

        if (Auth::can('platform.utilities.manage')) {
            $items[] = [
                'key' => 'utilities', 'label' => 'Utilities', 'href' => 'app.php?r=utilities',
                'icon' => '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.4-3.4a4 4 0 01-5.4 5.4L6.5 20.5a2 2 0 01-2.8-2.8L12.9 8.5a4 4 0 015.4-5.4l-3.4 3.4z"/>',
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
