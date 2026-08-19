<?php
declare(strict_types=1);

/**
 * Idempotent demo/dev seed. Safe to re-run — everything is INSERT IGNORE or
 * keyed on a unique column. NOT for production: passwords are a shared demo
 * password, printed below.
 *
 * Run: php database/seed.php
 */

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';

$pdo = Database::pdo();
$DEMO_PASSWORD = 'Password123!';
$hash = password_hash($DEMO_PASSWORD, PASSWORD_DEFAULT);

function insertIgnore(PDO $pdo, string $sql, array $params = []): void
{
    $pdo->prepare($sql)->execute($params);
}

function idBy(PDO $pdo, string $table, string $col, string $val): int
{
    $stmt = $pdo->prepare("SELECT id FROM `$table` WHERE `$col` = :v");
    $stmt->execute(['v' => $val]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        throw new RuntimeException("Seed lookup failed: $table.$col = $val");
    }
    return (int) $id;
}

echo "Seeding roles...\n";
$roles = [
    ['super_admin', 'Super Administrator', 0],
    ['management', 'Management', 0],
    ['administrator', 'Administrator', 0],
    ['centre_manager', 'Centre Manager', 1],
    ['accountant', 'Accountant', 0],
    ['cashier', 'Cashier', 1],
    ['instructor', 'Instructor', 1],
    ['receptionist', 'Receptionist', 1],
    ['student', 'Student', 0],
    ['applicant', 'Applicant', 0],
    ['affiliate', 'Affiliate', 0],
    // Recruitment (docs/architecture/16-careers-portal.md §8) — `job_applicant` is
    // deliberately distinct from `applicant` above, which already means "programme
    // applicant". `recruiter` is centre-scopable like centre_manager/cashier/instructor;
    // the others are global.
    ['job_applicant', 'Job Applicant', 0],
    ['recruitment_admin', 'Recruitment Administrator', 0],
    ['recruiter', 'Recruiter', 1],
    ['interviewer', 'Interviewer', 0],
    ['reporting_user', 'Reporting User', 0],
];
foreach ($roles as [$code, $name, $scopable]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO roles (code, name, is_scopable) VALUES (:c,:n,:s)', ['c' => $code, 'n' => $name, 's' => $scopable]);
}

echo "Seeding permissions...\n";
$permissions = [
    ['identity.user.view_any', 'identity'], ['identity.user.create', 'identity'],
    ['identity.user.update', 'identity'], ['identity.user.suspend', 'identity'],
    ['identity.role.assign', 'identity'], ['identity.profile.update', 'identity'],
    ['staff.member.view_any', 'staff'], ['staff.member.assign_centre', 'staff'],
    ['education.programme.view_any', 'education'], ['education.programme.create', 'education'],
    ['education.programme.update', 'education'], ['education.programme.approve', 'education'],
    ['education.programme.publish', 'education'],
    ['operations.cohort.manage', 'operations'], ['operations.session.schedule', 'operations'],
    ['operations.attendance.mark', 'operations'], ['operations.attendance.view_any', 'operations'],
    ['operations.room.manage', 'operations'], ['operations.equipment.manage', 'operations'],
    ['platform.setting.update', 'platform'], ['platform.audit.view', 'platform'],
    ['subscriptions.package.manage', 'subscriptions'],
    ['subscriptions.subscription.view_any', 'subscriptions'],
    ['subscriptions.subscription.activate', 'subscriptions'],
    ['subscriptions.override.grant', 'subscriptions'],
    ['admissions.application.view_any', 'admissions'],
    ['admissions.application.create', 'admissions'],
    ['admissions.application.review', 'admissions'],
    ['admissions.application.approve', 'admissions'],
    ['admissions.application.reject', 'admissions'],
    ['admissions.enrolment.create', 'admissions'],
    ['admissions.enrolment.transfer', 'admissions'],
    ['education.course.create', 'education'],
    ['education.course.update', 'education'],
    ['education.lesson.view', 'education'],
    ['education.assignment.grade', 'education'],
    ['education.certificate.issue', 'education'],
    ['finance.invoice.view_any', 'finance'],
    ['finance.invoice.create', 'finance'],
    ['finance.invoice.void', 'finance'],
    ['finance.payment.record', 'finance'],
    ['finance.payment.verify', 'finance'],
    ['finance.receipt.issue', 'finance'],
    ['finance.refund.create', 'finance'],
    ['finance.refund.approve', 'finance'],
    ['finance.expense.record', 'finance'],
    ['finance.expense.approve', 'finance'],
    ['finance.report.view', 'finance'],
    ['finance.reconciliation.run', 'finance'],
    ['comms.conversation.moderate', 'comms'],
    ['comms.announcement.publish', 'comms'],
    // Recruitment (docs/architecture/16-careers-portal.md §8). `application.decide` is
    // kept separate from `.review`, mirroring admissions' "approve is never implied by
    // review" rule (03-rbac.md §3).
    ['recruitment.job.manage', 'recruitment'],
    ['recruitment.application.view_any', 'recruitment'],
    ['recruitment.application.review', 'recruitment'],
    ['recruitment.application.decide', 'recruitment'],
    ['recruitment.interview.manage', 'recruitment'],
    ['recruitment.interview.feedback', 'recruitment'],
    ['recruitment.note.manage', 'recruitment'],
    ['recruitment.report.view', 'recruitment'],
    ['recruitment.email_template.manage', 'recruitment'],
];
foreach ($permissions as [$code, $module]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO permissions (code, module) VALUES (:c,:m)', ['c' => $code, 'm' => $module]);
}

echo "Mapping role -> permissions...\n";
$grants = [
    'super_admin' => array_column($permissions, 0), // short-circuited by Auth anyway; grant all for completeness
    'management' => [
        'identity.user.view_any', 'staff.member.view_any', 'staff.member.assign_centre',
        'education.programme.view_any', 'education.programme.approve',
        'operations.cohort.manage', 'operations.attendance.view_any',
        'platform.audit.view',
        'subscriptions.subscription.view_any',
        // Decision 9: a centre manager recommends, management decides.
        'admissions.application.view_any', 'admissions.application.approve',
        'admissions.application.reject', 'admissions.enrolment.transfer',
        'education.lesson.view', 'education.certificate.issue',
        // §8: management approves refunds and expenses; it does not raise them.
        'finance.invoice.view_any', 'finance.refund.approve',
        'finance.expense.approve', 'finance.report.view',
        'comms.announcement.publish',
    ],
    'administrator' => [
        'identity.user.view_any', 'identity.user.create', 'identity.user.update', 'identity.user.suspend',
        'identity.role.assign', 'staff.member.view_any', 'staff.member.assign_centre',
        'education.programme.view_any', 'education.programme.create', 'education.programme.update',
        'education.programme.publish', 'platform.setting.update',
        'subscriptions.package.manage', 'subscriptions.subscription.view_any',
        'subscriptions.subscription.activate', 'subscriptions.override.grant',
        'admissions.application.view_any', 'admissions.application.create',
        'admissions.application.review', 'admissions.application.approve',
        'admissions.application.reject', 'admissions.enrolment.create',
        'education.course.create', 'education.course.update', 'education.lesson.view',
        'education.certificate.issue',
        'comms.conversation.moderate', 'comms.announcement.publish',
    ],
    'centre_manager' => [
        'identity.user.view_any', 'staff.member.view_any',
        'education.programme.view_any',
        'operations.cohort.manage', 'operations.session.schedule',
        'operations.attendance.mark', 'operations.attendance.view_any',
        'operations.room.manage', 'operations.equipment.manage',
        // Reviews and admits at their own centre, but cannot approve — Decision 9.
        'admissions.application.view_any', 'admissions.application.review',
        'admissions.enrolment.create',
        'education.lesson.view',
        'finance.invoice.view_any', 'finance.expense.record', 'finance.report.view',
    ],
    // Activation is payment-driven once Phase 9 lands, and the accountant is who verifies
    // payments (05-finance-payments.md) — so activation sits with them, not the cashier.
    'accountant' => [
        'education.programme.view_any',
        'subscriptions.subscription.view_any', 'subscriptions.subscription.activate',
        // The full finance desk, minus refund APPROVAL — that sits with management.
        'finance.invoice.view_any', 'finance.invoice.create', 'finance.invoice.void',
        'finance.payment.record', 'finance.payment.verify', 'finance.receipt.issue',
        'finance.refund.create', 'finance.expense.record', 'finance.expense.approve',
        'finance.report.view', 'finance.reconciliation.run',
        'identity.user.view_any',
    ],
    // §8, the separation-of-duties spine: a cashier takes money and issues receipts.
    // They deliberately do NOT get payment.verify, invoice.void, refund.create or
    // report.view — the person handling cash cannot also confirm money that never
    // arrived, reverse a charge, or hide it by voiding.
    'cashier' => [
        'finance.invoice.view_any', 'finance.payment.record', 'finance.receipt.issue',
    ],
    'instructor' => [
        'education.programme.view_any', 'operations.session.schedule',
        'operations.attendance.mark', 'operations.attendance.view_any',
        // `education.course.update` is ◐ in 03-rbac.md §5 — only for assigned courses.
        // Scoping to "assigned" is not modelled yet, so the safer half is granted: an
        // instructor can read course content and grade their own students' work, but
        // cannot edit the syllabus. Noted in 13-lms.md §8.
        'education.lesson.view', 'education.assignment.grade',
    ],
    // Front desk: takes applications from walk-ins, but has no say in the decision.
    'receptionist' => [
        'education.programme.view_any',
        'admissions.application.view_any', 'admissions.application.create',
    ],
    'student' => ['education.programme.view_any', 'operations.attendance.view_any'],
    // Ownership-scoped: the controller constrains these to the applicant's own records.
    'applicant' => ['education.programme.view_any', 'admissions.application.view_any'],
    'affiliate' => ['education.programme.view_any'],
    // Recruitment. `job_applicant` gets no permission grant at all — the careers portal's
    // applicant-side routes never call Auth::requirePermission(), only ownership checks
    // (Fig. 3 of 16-careers-portal.md), so there is nothing to grant.
    'job_applicant' => [],
    'recruitment_admin' => [
        'recruitment.job.manage', 'recruitment.application.view_any',
        'recruitment.application.review', 'recruitment.application.decide',
        'recruitment.interview.manage', 'recruitment.note.manage',
        'recruitment.report.view', 'recruitment.email_template.manage',
    ],
    'recruiter' => [
        'recruitment.application.view_any', 'recruitment.application.review',
        'recruitment.note.manage', 'recruitment.interview.manage',
        // A recruiter is a valid panelist candidate (InterviewController::store()'s
        // panelist query includes the role) — they need the feedback permission too,
        // or they could be assigned to a panel and then blocked from using it.
        'recruitment.interview.feedback',
    ],
    // Sees only interviews they are assigned to (ownership-scoped in InterviewController),
    // not a broad view_any — this is the only permission an interviewer needs.
    'interviewer' => ['recruitment.interview.feedback'],
    'reporting_user' => ['recruitment.report.view'],
];
foreach ($grants as $roleCode => $permCodes) {
    $roleId = idBy($pdo, 'roles', 'code', $roleCode);
    foreach (array_unique($permCodes) as $permCode) {
        $permId = idBy($pdo, 'permissions', 'code', $permCode);
        insertIgnore($pdo, 'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:r,:p)', ['r' => $roleId, 'p' => $permId]);
    }
}

echo "Seeding centres (README §12 — Gwagwalada Hub, Kubwa Hub)...\n";
insertIgnore($pdo, 'INSERT IGNORE INTO centres (code, name, slug, city, state, status) VALUES (:c,:n,:s,:city,:st,:status)', [
    'c' => 'GWG', 'n' => 'Gwagwalada Hub', 's' => 'gwagwalada', 'city' => 'Gwagwalada', 'st' => 'FCT', 'status' => 'active',
]);
insertIgnore($pdo, 'INSERT IGNORE INTO centres (code, name, slug, city, state, status) VALUES (:c,:n,:s,:city,:st,:status)', [
    'c' => 'KBW', 'n' => 'Kubwa Hub', 's' => 'kubwa', 'city' => 'Kubwa', 'st' => 'FCT', 'status' => 'active',
]);
$gwgId = idBy($pdo, 'centres', 'code', 'GWG');
$kbwId = idBy($pdo, 'centres', 'code', 'KBW');

echo "Seeding rooms...\n";
$rooms = [
    [$gwgId, 'Room 2', 'classroom', 30], [$gwgId, 'Computer Lab', 'computer_lab', 24],
    [$kbwId, 'Lab 1', 'computer_lab', 24], [$kbwId, 'Room 3', 'classroom', 28], [$kbwId, 'Lab 2', 'computer_lab', 20],
];
foreach ($rooms as [$centreId, $name, $type, $cap]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO rooms (centre_id, name, type, capacity, status) VALUES (:c,:n,:t,:cap,:st)', [
        'c' => $centreId, 'n' => $name, 't' => $type, 'cap' => $cap, 'st' => 'available',
    ]);
}

echo "Seeding demo users...\n";
$users = [
    // email, first, last, role, centre_id (null = global)
    ['super@ultrademy.com', 'System', 'Administrator', 'super_admin', null],
    ['chidi.nwosu@ultrademy.com', 'Chidi', 'Nwosu', 'administrator', null],
    ['sarah.bello@ultrademy.com', 'Sarah', 'Bello', 'management', null],
    ['emeka.obi@ultrademy.com', 'Emeka', 'Obi', 'centre_manager', $kbwId],
    ['manager.gwagwalada@ultrademy.com', 'Ada', 'Umeh', 'centre_manager', $gwgId],
    ['ifeoma.chukwu@ultrademy.com', 'Ifeoma', 'Chukwu', 'accountant', null],
    ['tunde.bakare@ultrademy.com', 'Tunde', 'Bakare', 'cashier', $gwgId],
    ['grace.adeyemi@ultrademy.com', 'Grace', 'Adeyemi', 'instructor', $gwgId],
    ['blessing.eze@ultrademy.com', 'Blessing', 'Eze', 'student', null],
    ['kelvin.musa@ultrademy.com', 'Kelvin', 'Musa', 'student', null],
];
foreach ($users as [$email, $first, $last, $roleCode, $centreId]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO users (email, password_hash, status, email_verified_at) VALUES (:e,:h,:s,NOW())', [
        'e' => $email, 'h' => $hash, 's' => 'active',
    ]);
    $userId = idBy($pdo, 'users', 'email', $email);
    insertIgnore($pdo, 'INSERT IGNORE INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)', [
        'u' => $userId, 'f' => $first, 'l' => $last,
    ]);
    $roleId = idBy($pdo, 'roles', 'code', $roleCode);
    insertIgnore($pdo, 'INSERT IGNORE INTO user_roles (user_id, role_id, centre_id) VALUES (:u,:r,:c)', [
        'u' => $userId, 'r' => $roleId, 'c' => $centreId,
    ]);
}
// Point each centre at its manager now that the users exist.
insertIgnore($pdo, 'UPDATE centres SET manager_user_id = :u WHERE id = :c', ['u' => idBy($pdo, 'users', 'email', 'manager.gwagwalada@ultrademy.com'), 'c' => $gwgId]);
insertIgnore($pdo, 'UPDATE centres SET manager_user_id = :u WHERE id = :c', ['u' => idBy($pdo, 'users', 'email', 'emeka.obi@ultrademy.com'), 'c' => $kbwId]);
// staff_centres roster rows for anyone with a centre-scoped role.
foreach ($users as [$email, , , $roleCode, $centreId]) {
    if ($centreId === null) { continue; }
    insertIgnore($pdo, 'INSERT IGNORE INTO staff_centres (user_id, centre_id, is_primary) VALUES (:u,:c,1)', [
        'u' => idBy($pdo, 'users', 'email', $email), 'c' => $centreId,
    ]);
}

echo "Seeding programme categories...\n";
foreach ([['Technology', 'technology'], ['Business', 'business'], ['Creative', 'creative']] as [$name, $slug]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO programme_categories (name, slug) VALUES (:n,:s)', ['n' => $name, 's' => $slug]);
}

echo "Seeding programmes (same catalogue introduced on the public site in Phase 2)...\n";
// [code, title, slug, category slug, delivery_mode, weeks, fee_naira, centre codes[]]
$programmes = [
    ['PRG-WEBDEV', 'Web Development', 'web-development', 'technology', 'hybrid', 12, 45000, ['GWG', 'KBW']],
    ['PRG-DATAAN', 'Data Analysis', 'data-analysis', 'technology', 'online', 10, 40000, []],
    ['PRG-DIGMKT', 'Digital Marketing', 'digital-marketing', 'business', 'physical', 8, 35000, ['GWG']],
    ['PRG-GFXDSN', 'Graphic Design', 'graphic-design', 'creative', 'hybrid', 10, 38000, ['KBW']],
    ['PRG-NETFND', 'Networking Fundamentals', 'networking-fundamentals', 'technology', 'physical', 9, 42000, ['KBW']],
];
foreach ($programmes as [$code, $title, $slug, $catSlug, $mode, $weeks, $feeNaira, $centreCodes]) {
    $catId = idBy($pdo, 'programme_categories', 'slug', $catSlug);
    insertIgnore($pdo, 'INSERT IGNORE INTO programmes
        (code, title, slug, category_id, description, duration_weeks, delivery_mode, fee_amount, currency, status, published_at)
        VALUES (:code,:title,:slug,:cat,:desc,:weeks,:mode,:fee,\'NGN\',\'published\',NOW())', [
        'code' => $code, 'title' => $title, 'slug' => $slug, 'cat' => $catId,
        'desc' => "Practical, career-focused training in $title.",
        'weeks' => $weeks, 'mode' => $mode, 'fee' => $feeNaira * 100,
    ]);
    $progId = idBy($pdo, 'programmes', 'code', $code);
    foreach ($centreCodes as $cc) {
        $centreId = idBy($pdo, 'centres', 'code', $cc);
        insertIgnore($pdo, 'INSERT IGNORE INTO programme_centres (programme_id, centre_id) VALUES (:p,:c)', ['p' => $progId, 'c' => $centreId]);
    }
}

echo "Seeding a running cohort + class group + timetable for Web Development...\n";
$webDevId = idBy($pdo, 'programmes', 'code', 'PRG-WEBDEV');
insertIgnore($pdo, 'INSERT IGNORE INTO cohorts (programme_id, centre_id, code, name, starts_on, ends_on, capacity, status)
    VALUES (:p,:c,:code,:name,:starts,:ends,:cap,\'running\')', [
    'p' => $webDevId, 'c' => $gwgId, 'code' => 'WEBDEV-GWG-A', 'name' => 'Web Development — Cohort A',
    'starts' => date('Y-m-d', strtotime('-3 weeks')), 'ends' => date('Y-m-d', strtotime('+9 weeks')), 'cap' => 25,
]);
$cohortId = idBy($pdo, 'cohorts', 'code', 'WEBDEV-GWG-A');
$graceId = idBy($pdo, 'users', 'email', 'grace.adeyemi@ultrademy.com');
insertIgnore($pdo, 'INSERT IGNORE INTO class_groups (cohort_id, instructor_user_id, name, capacity) VALUES (:co,:i,:n,:cap)', [
    'co' => $cohortId, 'i' => $graceId, 'n' => 'Cohort A — Group 1', 'cap' => 25,
]);
$groupRow = $pdo->query("SELECT id FROM class_groups WHERE cohort_id = $cohortId LIMIT 1")->fetch();
$groupId = (int) $groupRow['id'];
$room2Id = idBy($pdo, 'rooms', 'name', 'Room 2');

// A couple of sessions anchored to "this week" so the timetable looks current when freshly seeded.
$mon = new DateTime('monday this week');
insertIgnore($pdo, 'INSERT IGNORE INTO class_sessions (class_group_id, room_id, topic, starts_at, ends_at, mode, status)
    VALUES (:g,:r,:t,:s,:e,\'physical\',\'scheduled\')', [
    'g' => $groupId, 'r' => $room2Id, 't' => 'HTML & CSS Foundations',
    's' => $mon->format('Y-m-d') . ' 09:00:00', 'e' => $mon->format('Y-m-d') . ' 11:00:00',
]);
$wed = (clone $mon)->modify('+2 days');
insertIgnore($pdo, 'INSERT IGNORE INTO class_sessions (class_group_id, room_id, topic, starts_at, ends_at, mode, status)
    VALUES (:g,:r,:t,:s,:e,\'physical\',\'scheduled\')', [
    'g' => $groupId, 'r' => $room2Id, 't' => 'JavaScript Basics',
    's' => $wed->format('Y-m-d') . ' 09:00:00', 'e' => $wed->format('Y-m-d') . ' 11:00:00',
]);

echo "Enrolling two demo students into the cohort (for attendance testing)...\n";
foreach (['blessing.eze@ultrademy.com' => 'UD-2026-0001', 'kelvin.musa@ultrademy.com' => 'UD-2026-0002'] as $email => $studentNo) {
    $uid = idBy($pdo, 'users', 'email', $email);
    insertIgnore($pdo, 'INSERT IGNORE INTO enrolments (student_no, user_id, programme_id, cohort_id, centre_id, status)
        VALUES (:no,:u,:p,:c,:centre,\'active\')', [
        'no' => $studentNo, 'u' => $uid, 'p' => $webDevId, 'c' => $cohortId, 'centre' => $gwgId,
    ]);
}

echo "Seeding a course with modules, lessons and an assignment...\n";
insertIgnore($pdo, "INSERT IGNORE INTO courses (title, slug, description, objectives, prerequisites, status, standalone)
    VALUES (:t,:s,:d,:o,:p,'published',0)", [
    't' => 'Web Development Foundations',
    's' => 'web-development-foundations',
    'd' => 'The groundwork every web developer needs: how the web works, HTML, CSS and your first JavaScript.',
    'o' => "Explain how a browser requests and renders a page.\nBuild a responsive page from a design.\nWrite JavaScript that reacts to user input.",
    'p' => 'Basic computer literacy. No prior coding experience required.',
]);
$courseId = idBy($pdo, 'courses', 'slug', 'web-development-foundations');

// Reachable only through a programme — so link it to Web Development.
insertIgnore($pdo, 'INSERT IGNORE INTO programme_courses (programme_id, course_id, sort_order) VALUES (:p,:c,0)', [
    'p' => idBy($pdo, 'programmes', 'code', 'PRG-WEBDEV'), 'c' => $courseId,
]);

// [module title, summary, [ [lesson title, type, minutes, is_preview, body], ... ] ]
$modules = [
    ['How the Web Works', 'Orientation before any code is written.', [
        ['The request/response cycle', 'text', 15, 1, "Every page you open starts with your browser sending an HTTP request to a server.\n\nThe server replies with a response: a status code, some headers, and usually a body of HTML. Understanding this exchange is the foundation for everything else in this course."],
        ['Anatomy of a URL', 'text', 10, 0, "A URL has parts, and each one tells the browser something different: scheme, host, path, query string and fragment.\n\nKnowing which part does what makes debugging far quicker."],
    ]],
    ['HTML & CSS', 'Structure first, then presentation.', [
        ['Semantic HTML', 'text', 25, 0, "HTML describes what content *is*, not what it looks like.\n\nUsing a heading because it is a heading — rather than because it renders large — is what makes a page work with screen readers and search engines."],
        ['Layout with Flexbox', 'text', 30, 0, "Flexbox lays out items along a single axis and handles the spacing between them.\n\nIt replaced a decade of float-based hacks and is still the right tool for most one-dimensional layouts."],
    ]],
    ['JavaScript Basics', 'Making a page respond.', [
        ['Variables and types', 'text', 20, 0, "JavaScript has a small set of primitive types and one very flexible object type.\n\nMost early confusion comes from not knowing which one you are holding."],
        ['Listening for events', 'text', 25, 0, "An event listener runs your function when something happens — a click, a keypress, a form submission.\n\nThis is where a static page becomes an application."],
    ]],
];
foreach ($modules as $mi => [$mTitle, $mSummary, $lessons]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO modules (course_id, title, summary, sort_order) VALUES (:c,:t,:s,:o)', [
        'c' => $courseId, 't' => $mTitle, 's' => $mSummary, 'o' => $mi,
    ]);
    $mStmt = $pdo->prepare('SELECT id FROM modules WHERE course_id = :c AND title = :t');
    $mStmt->execute(['c' => $courseId, 't' => $mTitle]);
    $moduleId = (int) $mStmt->fetchColumn();

    foreach ($lessons as $li => [$lTitle, $lType, $lMin, $lPreview, $lBody]) {
        $exists = $pdo->prepare('SELECT id FROM lessons WHERE module_id = :m AND title = :t');
        $exists->execute(['m' => $moduleId, 't' => $lTitle]);
        if ($exists->fetchColumn() === false) {
            insertIgnore($pdo, 'INSERT INTO lessons (module_id, title, content_type, body, duration_minutes, sort_order, is_preview)
                VALUES (:m,:t,:ct,:b,:d,:o,:p)', [
                'm' => $moduleId, 't' => $lTitle, 'ct' => $lType, 'b' => $lBody,
                'd' => $lMin, 'o' => $li, 'p' => $lPreview,
            ]);
        }
    }
}
insertIgnore($pdo, 'UPDATE courses c SET c.estimated_minutes = (
    SELECT COALESCE(SUM(l.duration_minutes),0) FROM lessons l JOIN modules m ON m.id = l.module_id WHERE m.course_id = c.id
) WHERE c.id = :c', ['c' => $courseId]);

$aExists = $pdo->prepare('SELECT id FROM assignments WHERE course_id = :c AND title = :t');
$aExists->execute(['c' => $courseId, 't' => 'Build a personal profile page']);
if ($aExists->fetchColumn() === false) {
    insertIgnore($pdo, "INSERT INTO assignments (course_id, title, instructions, due_at, max_score, allows_file, allows_text, allows_resubmission, status)
        VALUES (:c,:t,:i,:due,100,1,1,1,'published')", [
        'c' => $courseId,
        't' => 'Build a personal profile page',
        'i' => 'Build a single HTML page about yourself using semantic markup and a Flexbox layout. Attach your .zip, or paste your HTML in the text box.',
        'due' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
    ]);
}

echo "Seeding feature registry (04-subscriptions-entitlements.md §3)...\n";
// [code, name, module, limit_type]
$features = [
    ['calendar',               'Calendar',                'operations', 'none'],
    ['events',                 'Events',                  'operations', 'none'],
    ['programme_applications', 'Programme Applications',  'admissions', 'count'],
    ['online_learning',        'Online Learning',         'education',  'none'],
    ['assignments',            'Assignments',             'education',  'none'],
    ['assessments',            'Assessments',             'education',  'none'],
    ['certificates',           'Certificates',            'education',  'none'],
    ['premium_resources',      'Premium Resources',       'education',  'none'],
    ['special_programmes',     'Special Programmes',      'education',  'none'],
    ['chat_direct',            'Direct Chat',             'comms',      'none'],
    ['chat_groups',            'Group Chat',              'comms',      'count'],
    ['affiliate_programme',    'Affiliate Programme',     'affiliate',  'none'],
    ['file_storage',           'File Storage',            'platform',   'bytes'],
];
foreach ($features as [$code, $name, $module, $limitType]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO features (code, name, module, limit_type) VALUES (:c,:n,:m,:l)', [
        'c' => $code, 'n' => $name, 'm' => $module, 'l' => $limitType,
    ]);
}

echo "Seeding packages...\n";
// [code, name, price_naira, billing_period, duration_days, sort_order, description]
$packages = [
    ['basic',    'Basic',    0,     'monthly', 30,  1, 'Account essentials — browse and stay in touch.'],
    ['standard', 'Standard', 5000,  'monthly', 30,  2, 'Calendar, applications and online learning.'],
    ['premium',  'Premium',  15000, 'monthly', 30,  3, 'Full online learning with assignments, assessments and certificates.'],
    ['advanced', 'Advanced', 30000, 'monthly', 30,  4, 'Everything in Premium, plus premium resources and unlimited applications.'],
];
foreach ($packages as [$code, $name, $priceNaira, $period, $days, $sort, $desc]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO packages (code, name, description, price_amount, currency, billing_period, duration_days, status, sort_order)
        VALUES (:c,:n,:d,:p,\'NGN\',:bp,:dd,\'active\',:so)', [
        'c' => $code, 'n' => $name, 'd' => $desc, 'p' => $priceNaira * 100,
        'bp' => $period, 'dd' => $days, 'so' => $sort,
    ]);
}

echo "Mapping package -> features (the §4 matrix — data, never code)...\n";
// null = unlimited; a feature absent from a package's list is simply OFF.
// Storage in bytes: 100 MB / 1 GB / 10 GB / 50 GB.
const MB = 1048576;
const GB = 1073741824;
$matrix = [
    'basic' => [
        'chat_direct' => null,
        'affiliate_programme' => null,          // Decision 15: available at every tier
        'file_storage' => 100 * MB,
    ],
    'standard' => [
        'calendar' => null, 'events' => null,
        'programme_applications' => 1,
        'online_learning' => null,
        'chat_direct' => null, 'chat_groups' => 2,
        'affiliate_programme' => null,
        'file_storage' => 1 * GB,
    ],
    'premium' => [
        'calendar' => null, 'events' => null,
        'programme_applications' => 3,
        'online_learning' => null, 'assignments' => null, 'assessments' => null, 'certificates' => null,
        'chat_direct' => null, 'chat_groups' => 10,
        'affiliate_programme' => null,
        'file_storage' => 10 * GB,
    ],
    'advanced' => [
        'calendar' => null, 'events' => null,
        'programme_applications' => null,       // unlimited
        'online_learning' => null, 'assignments' => null, 'assessments' => null, 'certificates' => null,
        'premium_resources' => null, 'special_programmes' => null,
        'chat_direct' => null, 'chat_groups' => null,
        'affiliate_programme' => null,
        'file_storage' => 50 * GB,
    ],
];
foreach ($matrix as $pkgCode => $featureLimits) {
    $pkgId = idBy($pdo, 'packages', 'code', $pkgCode);
    foreach ($featureLimits as $featCode => $limit) {
        $featId = idBy($pdo, 'features', 'code', $featCode);
        insertIgnore($pdo, 'INSERT IGNORE INTO package_features (package_id, feature_id, limit_value) VALUES (:p,:f,:l)', [
            'p' => $pkgId, 'f' => $featId, 'l' => $limit,
        ]);
    }
}

echo "Giving one demo student an active Premium subscription (for entitlement testing)...\n";
$blessingId = idBy($pdo, 'users', 'email', 'blessing.eze@ultrademy.com');
$premiumId  = idBy($pdo, 'packages', 'code', 'premium');
$existing = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = :u AND status = 'active'");
$existing->execute(['u' => $blessingId]);
if ($existing->fetchColumn() === false) {
    insertIgnore($pdo, "INSERT INTO subscriptions (user_id, package_id, status, starts_at, ends_at)
        VALUES (:u,:p,'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))", ['u' => $blessingId, 'p' => $premiumId]);
}
// Kelvin deliberately gets NO subscription, so the paywall path is demonstrable too.

echo "Seeding baseline settings...\n";
$settings = [
    ['site_name', json_encode('UltrAdemy'), 'general', 1],
    ['site_timezone', json_encode('Africa/Lagos'), 'general', 1],
    ['support_email', json_encode('info@ultrademy.com'), 'general', 1],
    // Decision 19: bank details are a GLOBAL setting, not hard-coded and not per centre.
    // Placeholders — real details must be entered in Settings before going live.
    ['bank_name', json_encode(''), 'finance', 0],
    ['bank_account_name', json_encode(''), 'finance', 0],
    ['bank_account_number', json_encode(''), 'finance', 0],
    // Gateway credentials live here rather than in .env so an administrator can rotate
    // them without a deploy. Empty = that provider is simply not offered.
    ['paystack_secret_key', json_encode(''), 'finance', 0],
    ['flutterwave_secret_key', json_encode(''), 'finance', 0],
    ['flutterwave_webhook_hash', json_encode(''), 'finance', 0],
    // Same convention as the gateway credentials above — empty provider = CAPTCHA is
    // simply not enforced (Captcha::isEnabled()), and an admin can turn it on later
    // without a deploy. Provider was an explicit open decision in the original
    // architecture report; value is one of: turnstile, recaptcha, hcaptcha.
    ['captcha_provider', json_encode(''), 'security', 0],
    ['captcha_site_key', json_encode(''), 'security', 0],
    ['captcha_secret_key', json_encode(''), 'security', 0],
];
foreach ($settings as [$key, $value, $group, $public]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO settings (`key`, value, `group`, is_public) VALUES (:k,:v,:g,:p)', [
        'k' => $key, 'v' => $value, 'g' => $group, 'p' => $public,
    ]);
}

echo "Seeding recruitment demo users...\n";
foreach ([
    ['ngozi.eze@ultrademy.com', 'Ngozi', 'Eze', 'recruitment_admin', null],
    ['femi.okoro@ultrademy.com', 'Femi', 'Okoro', 'recruiter', $gwgId],
    ['bola.adeleke@ultrademy.com', 'Bola', 'Adeleke', 'reporting_user', null],
] as [$email, $first, $last, $roleCode, $centreId]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO users (email, password_hash, status, email_verified_at) VALUES (:e,:h,:s,NOW())', [
        'e' => $email, 'h' => $hash, 's' => 'active',
    ]);
    $uid = idBy($pdo, 'users', 'email', $email);
    insertIgnore($pdo, 'INSERT IGNORE INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)', [
        'u' => $uid, 'f' => $first, 'l' => $last,
    ]);
    $roleId = idBy($pdo, 'roles', 'code', $roleCode);
    insertIgnore($pdo, 'INSERT IGNORE INTO user_roles (user_id, role_id, centre_id) VALUES (:u,:r,:c)', [
        'u' => $uid, 'r' => $roleId, 'c' => $centreId,
    ]);
    if ($centreId !== null) {
        insertIgnore($pdo, 'INSERT IGNORE INTO staff_centres (user_id, centre_id, is_primary) VALUES (:u,:c,1)', ['u' => $uid, 'c' => $centreId]);
    }
}

echo "Seeding recruitment departments and categories...\n";
foreach ([['Technology', 'technology'], ['Training & Education', 'training-education'], ['Centre Operations', 'centre-operations'], ['Administration', 'administration']] as [$n, $s]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO departments (name, slug) VALUES (:n,:s)', ['n' => $n, 's' => $s]);
}
foreach ([['Software Development', 'software-development'], ['Instruction', 'instruction'], ['Front Desk', 'front-desk'], ['IT Support', 'it-support']] as [$n, $s]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO job_categories (name, slug) VALUES (:n,:s)', ['n' => $n, 's' => $s]);
}

echo "Seeding demo job postings (careers.ultrademy.com)...\n";
$deptTech = idBy($pdo, 'departments', 'slug', 'technology');
$deptTraining = idBy($pdo, 'departments', 'slug', 'training-education');
$deptOps = idBy($pdo, 'departments', 'slug', 'centre-operations');
$catDev = idBy($pdo, 'job_categories', 'slug', 'software-development');
$catInstr = idBy($pdo, 'job_categories', 'slug', 'instruction');
$catFrontDesk = idBy($pdo, 'job_categories', 'slug', 'front-desk');
$recruitmentAdminId = idBy($pdo, 'users', 'email', 'ngozi.eze@ultrademy.com');

$jobs = [
    // [title, slug, dept, cat, employment_type, work_mode, location_type, location_label, centre codes[], deadline_days, status]
    ['Full-Stack Developer', 'full-stack-developer', $deptTech, $catDev, 'full_time', 'hybrid', 'centre', null, ['GWG'], 30, 'published'],
    ['Web Development Instructor', 'web-development-instructor', $deptTraining, $catInstr, 'full_time', 'onsite', 'centre', null, ['KBW'], 21, 'published'],
    ['Remote Data Analysis Instructor', 'remote-data-analysis-instructor', $deptTraining, $catInstr, 'part_time', 'remote', 'remote', 'Remote', [], 45, 'published'],
    ['Front Desk Officer', 'front-desk-officer', $deptOps, $catFrontDesk, 'full_time', 'onsite', 'multiple_centres', 'Gwagwalada Hub & Kubwa Hub', ['GWG', 'KBW'], 14, 'published'],
    ['IT Support Intern', 'it-support-intern', $deptTech, $catDev, 'internship', 'onsite', 'centre', null, ['GWG'], 10, 'published'],
    ['Digital Marketing Assistant', 'digital-marketing-assistant', $deptOps, null, 'contract', 'hybrid', 'centre', null, ['KBW'], -5, 'closed'],
];
foreach ($jobs as [$title, $slug, $deptId, $catId, $empType, $mode, $locType, $locLabel, $centreCodes, $deadlineDays, $status]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO job_postings
        (title, slug, department_id, category_id, employment_type, work_mode, location_type, location_label,
         summary, responsibilities, requirements, qualifications, skills, experience_requirements,
         application_deadline, status, published_at, created_by)
        VALUES (:title,:slug,:dept,:cat,:emp,:mode,:loct,:locl,:summ,:resp,:req,:qual,:skills,:exp,:deadline,:status,
                CASE WHEN :status2 = \'published\' THEN NOW() ELSE NULL END, :by)', [
        'title' => $title, 'slug' => $slug, 'dept' => $deptId, 'cat' => $catId,
        'emp' => $empType, 'mode' => $mode, 'loct' => $locType, 'locl' => $locLabel,
        'summ' => "Join UltrAdemy as a $title and help us grow our reach across technology, training and operations.",
        'resp' => "Deliver excellent work as a $title.\nCollaborate with the wider UltrAdemy team.\nUphold UltrAdemy's standards of quality and care.",
        'req' => "Relevant experience for a $title role.\nStrong communication skills.\nAbility to work both independently and in a team.",
        'qual' => 'A relevant qualification or equivalent practical experience.',
        'skills' => 'Communication, problem-solving, attention to detail.',
        'exp' => '1-3 years in a related role, depending on seniority.',
        'deadline' => date('Y-m-d H:i:s', strtotime("+$deadlineDays days")),
        'status' => $status, 'status2' => $status, 'by' => $recruitmentAdminId,
    ]);
    $postingId = idBy($pdo, 'job_postings', 'slug', $slug);
    foreach ($centreCodes as $cc) {
        insertIgnore($pdo, 'INSERT IGNORE INTO job_posting_centres (job_posting_id, centre_id) VALUES (:p,:c)', [
            'p' => $postingId, 'c' => idBy($pdo, 'centres', 'code', $cc),
        ]);
    }
}
// One posting gets a couple of application questions, to exercise the question-builder path.
$fsdId = idBy($pdo, 'job_postings', 'slug', 'full-stack-developer');
foreach ([
    ['Why are you interested in this position?', 'long_text', 1, 0],
    ['How many years of professional PHP or JavaScript experience do you have?', 'number', 1, 1],
] as [$label, $type, $required, $sort]) {
    $exists = $pdo->prepare('SELECT id FROM job_questions WHERE job_posting_id = :p AND label = :l');
    $exists->execute(['p' => $fsdId, 'l' => $label]);
    if ($exists->fetchColumn() === false) {
        insertIgnore($pdo, 'INSERT INTO job_questions (job_posting_id, label, type, is_required, sort_order) VALUES (:p,:l,:t,:r,:s)', [
            'p' => $fsdId, 'l' => $label, 't' => $type, 'r' => $required, 's' => $sort,
        ]);
    }
}

echo "\nDone.\n";
echo "Demo login — any seeded email above, password: $DEMO_PASSWORD\n";
echo "Try: super@ultrademy.com / manager.gwagwalada@ultrademy.com / grace.adeyemi@ultrademy.com / blessing.eze@ultrademy.com\n";
