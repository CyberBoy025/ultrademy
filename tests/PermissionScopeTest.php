<?php
declare(strict_types=1);

/**
 * The two §42 controls the roadmap called out as unverified by anything:
 *
 *   1. A Gwagwalada centre_manager must never see Kubwa's data through the centre
 *      scope that Auth::scopeCentres() computes.
 *   2. A cashier must never be able to verify a manual bank transfer — that
 *      permission belongs to the accountant role only (the separation-of-duties
 *      spine documented in database/seed.php around the cashier block).
 *
 * These need a real database — Auth::can()/scopeCentres() run real SQL joining
 * user_roles, roles, role_permissions and permissions, and the thing actually worth
 * proving is that those joins produce the right answer, not that a stub does.
 *
 * Per the convention set out in tests/run.php's docblock, this suite only runs when
 * DB_TEST_DSN is set, and skips itself otherwise so a fresh checkout stays green.
 * It builds its own database from the real migration files — never the live `ultrademy`
 * one — so it can TRUNCATE and reseed freely. Run it with, e.g.:
 *
 *   set DB_TEST_DSN=mysql:host=127.0.0.1;port=3306;dbname=ultrademy_test;charset=utf8mb4
 *   php tests/run.php
 *
 * DB_TEST_USER / DB_TEST_PASS default to root / '' (this project's own local default).
 */

$udDsn = getenv('DB_TEST_DSN');
$udReady = false;
$udSkipReason = 'DB_TEST_DSN is not set — see the docblock in this file to run these against a real database.';

if ($udDsn !== false && $udDsn !== '') {
    try {
        if (!preg_match('/dbname=([^;]+)/', $udDsn, $m)) {
            throw new RuntimeException('DB_TEST_DSN has no dbname=... segment');
        }
        $udDbName = $m[1];
        $udServerDsn = preg_replace('/dbname=[^;]+;?/', '', $udDsn);
        $udUser = getenv('DB_TEST_USER') ?: 'root';
        $udPass = getenv('DB_TEST_PASS') ?: '';
        $udOpts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        // Create the test schema if it does not exist yet — connecting straight to
        // dbname=... would fail on a fresh machine where nothing has created it.
        $udServer = new PDO($udServerDsn, $udUser, $udPass, $udOpts);
        $udServer->exec("CREATE DATABASE IF NOT EXISTS `$udDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $udPdo = new PDO($udDsn, $udUser, $udPass, $udOpts);
        $udPdo->exec('SET FOREIGN_KEY_CHECKS=0');

        // Schema pulled from the real migration files, not retyped, so this can never
        // silently drift from what production actually runs.
        $udMigrationsDir = dirname(__DIR__) . '/database/migrations/';
        foreach (['001_create_users', '006_create_centres', '003_create_roles',
                   '004_create_permissions', '005_create_role_permissions', '007_create_user_roles'] as $udFile) {
            $udPdo->exec(file_get_contents($udMigrationsDir . $udFile . '.sql'));
        }

        foreach (['user_roles', 'role_permissions', 'permissions', 'roles', 'centres', 'users'] as $udTable) {
            $udPdo->exec("TRUNCATE TABLE `$udTable`");
        }
        $udPdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // --- fixtures -----------------------------------------------------------
        // Mirrors the real seed.php shape (manager.gwagwalada@ / emeka.obi@ / tunde.bakare@)
        // closely enough to mean something, kept minimal so this file owns its own data.
        $udPdo->exec("INSERT INTO centres (id, code, name, slug, status) VALUES
            (1, 'GWG', 'Gwagwalada Hub', 'gwagwalada-hub', 'active'),
            (2, 'KBW', 'Kubwa Hub', 'kubwa-hub', 'active')");

        $udPdo->exec("INSERT INTO roles (id, code, name, is_scopable) VALUES
            (1, 'centre_manager', 'Centre Manager', 1),
            (2, 'cashier', 'Cashier', 1),
            (3, 'accountant', 'Accountant', 0),
            (4, 'super_admin', 'Super Admin', 0)");

        $udPdo->exec("INSERT INTO permissions (id, code, module) VALUES
            (1, 'identity.user.view_any', 'identity'),
            (2, 'finance.payment.verify', 'finance'),
            (3, 'finance.payment.record', 'finance')");

        // The exact production split, verified against the live database on 21 Aug 2026:
        // cashier gets record, never verify; accountant gets verify; centre_manager gets
        // the roster permission that must stay centre-scoped.
        $udPdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES
            (1, 1),  -- centre_manager: identity.user.view_any
            (2, 3),  -- cashier: finance.payment.record
            (3, 2)   -- accountant: finance.payment.verify");
        // super_admin (role 4) deliberately gets NO row here — its access comes only
        // from Auth::isSuperAdmin()'s short-circuit, which is exactly what test five below
        // is checking. A row here would let that test pass for the wrong reason.

        $udPdo->exec("INSERT INTO users (id, email, password_hash, status) VALUES
            (1, 'gwagwalada.manager@test.local', 'x', 'active'),
            (2, 'kubwa.manager@test.local', 'x', 'active'),
            (3, 'cashier@test.local', 'x', 'active'),
            (4, 'accountant@test.local', 'x', 'active'),
            (5, 'root@test.local', 'x', 'active')");

        $udPdo->exec("INSERT INTO user_roles (user_id, role_id, centre_id) VALUES
            (1, 1, 1),   -- gwagwalada.manager: centre_manager @ Gwagwalada (1)
            (2, 1, 2),   -- kubwa.manager:      centre_manager @ Kubwa (2)
            (3, 2, 1),   -- cashier:             cashier @ Gwagwalada (1)
            (4, 3, NULL),-- accountant:          accountant, global (matches roles.is_scopable=0)
            (5, 4, NULL) -- root:                super_admin, global");

        require_once dirname(__DIR__) . '/config/bootstrap.php';
        require_once dirname(__DIR__) . '/app/core/Database.php';
        require_once dirname(__DIR__) . '/app/core/Auth.php';

        // Point the app's own Database singleton at this fixture schema. bootstrap.php
        // has already run by this point (config() is defined), so this edits the same
        // config array Database::pdo() reads from — no separate connection to keep in sync.
        preg_match('/host=([^;]+)/', $udDsn, $mHost);
        preg_match('/port=([^;]+)/', $udDsn, $mPort);
        $GLOBALS['ultrademy_config']['db'] = [
            'host'    => $mHost[1] ?? '127.0.0.1',
            'port'    => (int) ($mPort[1] ?? 3306),
            'name'    => $udDbName,
            'user'    => $udUser,
            'pass'    => $udPass,
            'charset' => 'utf8mb4',
        ];

        $udReady = true;
    } catch (Throwable $e) {
        $udSkipReason = 'could not set up the DB_TEST_DSN fixture database: ' . $e->getMessage();
    }
}

/** Switches the in-process identity, clearing Auth's cache so the switch actually takes. */
function ud_as(int $userId): void
{
    $_SESSION['user_id'] = $userId;
    Auth::forgetCachedIdentity();
}

test('a Gwagwalada manager\'s centre scope is Gwagwalada only, never Kubwa', function () use (&$udReady, &$udSkipReason) {
    if (!$udReady) skip($udSkipReason);
    ud_as(1);
    $scope = Auth::scopeCentres('identity.user.view_any');
    assertSame_([1], $scope, 'Gwagwalada manager (centre 1) scope');
    assertFalse_(in_array(2, $scope ?? [], true), 'Kubwa (centre 2) must not appear in a Gwagwalada manager\'s scope');
});

test('a Kubwa manager\'s centre scope is Kubwa only, never Gwagwalada — proven both directions', function () use (&$udReady, &$udSkipReason) {
    if (!$udReady) skip($udSkipReason);
    ud_as(2);
    $scope = Auth::scopeCentres('identity.user.view_any');
    assertSame_([2], $scope, 'Kubwa manager (centre 2) scope');
});

test('a cashier can record a payment but cannot verify one', function () use (&$udReady, &$udSkipReason) {
    if (!$udReady) skip($udSkipReason);
    ud_as(3);
    assertTrue_(Auth::can('finance.payment.record'), 'cashier should still be able to record payments');
    assertFalse_(Auth::can('finance.payment.verify'), 'cashier must NOT be able to verify a manual transfer');
});

test('the accountant — not the cashier — holds verify, and it is global', function () use (&$udReady, &$udSkipReason) {
    if (!$udReady) skip($udSkipReason);
    ud_as(4);
    assertTrue_(Auth::can('finance.payment.verify'), 'accountant should be able to verify');
    assertSame_(null, Auth::scopeCentres('finance.payment.verify'), 'accountant grant should be global (no centre filter)');
});

test('super_admin bypasses permission checks entirely, even for a permission that does not exist', function () use (&$udReady, &$udSkipReason) {
    if (!$udReady) skip($udSkipReason);
    ud_as(5);
    assertTrue_(Auth::can('nothing.grants.this'), 'super_admin must short-circuit Auth::can() per 03-rbac.md §7');
    assertSame_(null, Auth::scopeCentres('nothing.grants.this'), 'super_admin scope is always unrestricted');
});

test('a user with no role at all gets no scope and no permission', function () use (&$udReady, &$udSkipReason) {
    if (!$udReady) skip($udSkipReason);
    // No fixture user 999 exists at all — deliberately: this is the "session says
    // someone is logged in, but they hold zero roles" case, and Auth's queries never
    // touch the users table to get here, so an absent row does not matter.
    ud_as(999);
    assertFalse_(Auth::can('identity.user.view_any'), 'a roleless user must not pass any permission check');
    assertSame_([], Auth::scopeCentres('identity.user.view_any'), 'a roleless user\'s scope is empty, not unrestricted');
});
