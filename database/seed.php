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
    ],
    'administrator' => [
        'identity.user.view_any', 'identity.user.create', 'identity.user.update', 'identity.user.suspend',
        'identity.role.assign', 'staff.member.view_any', 'staff.member.assign_centre',
        'education.programme.view_any', 'education.programme.create', 'education.programme.update',
        'education.programme.publish', 'platform.setting.update',
    ],
    'centre_manager' => [
        'identity.user.view_any', 'staff.member.view_any',
        'education.programme.view_any',
        'operations.cohort.manage', 'operations.session.schedule',
        'operations.attendance.mark', 'operations.attendance.view_any',
        'operations.room.manage', 'operations.equipment.manage',
    ],
    'accountant' => ['education.programme.view_any'],
    'cashier' => [],
    'instructor' => [
        'education.programme.view_any', 'operations.session.schedule',
        'operations.attendance.mark', 'operations.attendance.view_any',
    ],
    'receptionist' => ['education.programme.view_any'],
    'student' => ['education.programme.view_any', 'operations.attendance.view_any'],
    'applicant' => ['education.programme.view_any'],
    'affiliate' => ['education.programme.view_any'],
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

echo "Seeding baseline settings...\n";
$settings = [
    ['site_name', json_encode('UltrAdemy'), 'general', 1],
    ['site_timezone', json_encode('Africa/Lagos'), 'general', 1],
    ['support_email', json_encode('info@ultrademy.com'), 'general', 1],
];
foreach ($settings as [$key, $value, $group, $public]) {
    insertIgnore($pdo, 'INSERT IGNORE INTO settings (`key`, value, `group`, is_public) VALUES (:k,:v,:g,:p)', [
        'k' => $key, 'v' => $value, 'g' => $group, 'p' => $public,
    ]);
}

echo "\nDone.\n";
echo "Demo login — any seeded email above, password: $DEMO_PASSWORD\n";
echo "Try: super@ultrademy.com / manager.gwagwalada@ultrademy.com / grace.adeyemi@ultrademy.com / blessing.eze@ultrademy.com\n";
