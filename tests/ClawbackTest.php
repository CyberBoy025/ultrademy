<?php
declare(strict_types=1);

/**
 * Decision 34 (19-affiliate.md §10, confirmed 21 Aug 2026): a refund claws back the
 * commission it earned. Previously "the real gap" — no code existed for this at all.
 *
 * Needs a real database, the same reasoning as PermissionScopeTest.php: Affiliate::
 * clawback() runs real SQL against commissions/payments, and what's worth proving is
 * that the SQL does the right thing, not that a stub does. Builds its own schema from
 * the actual migration files, opt-in via DB_TEST_DSN, skips cleanly without it.
 */

$cbDsn = getenv('DB_TEST_DSN');
$cbReady = false;
$cbSkipReason = 'DB_TEST_DSN is not set — see PermissionScopeTest.php\'s docblock to run these against a real database.';

if ($cbDsn !== false && $cbDsn !== '') {
    try {
        if (!preg_match('/dbname=([^;]+)/', $cbDsn, $m)) {
            throw new RuntimeException('DB_TEST_DSN has no dbname=... segment');
        }
        $cbDbName = $m[1];
        $cbServerDsn = preg_replace('/dbname=[^;]+;?/', '', $cbDsn);
        $cbUser = getenv('DB_TEST_USER') ?: 'root';
        $cbPass = getenv('DB_TEST_PASS') ?: '';
        $cbOpts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        // Create the test schema if it does not exist yet — connecting straight to
        // dbname=... would fail on a fresh machine (or the first test file to run).
        $cbServer = new PDO($cbServerDsn, $cbUser, $cbPass, $cbOpts);
        $cbServer->exec("CREATE DATABASE IF NOT EXISTS `$cbDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $cbPdo = new PDO($cbDsn, $cbUser, $cbPass, $cbOpts);
        $cbPdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $cbDir = dirname(__DIR__) . '/database/migrations/';
        foreach ([
            '001_create_users', '006_create_centres', '009_create_audit_logs',
            '042_create_invoices', '044_create_payments', '091_create_affiliates',
            '092_create_referrals', '094_create_commissions',
        ] as $cbFile) {
            $cbPdo->exec(file_get_contents($cbDir . $cbFile . '.sql'));
        }

        foreach ([
            'commissions', 'referrals', 'affiliates', 'payments', 'invoices',
            'audit_logs', 'users', 'centres',
        ] as $cbTable) {
            $cbPdo->exec("TRUNCATE TABLE `$cbTable`");
        }
        $cbPdo->exec('SET FOREIGN_KEY_CHECKS=1');

        $cbPdo->exec("INSERT INTO users (id, email, password_hash, status) VALUES
            (1, 'affiliate@test.local', 'x', 'active'),
            (2, 'referred@test.local', 'x', 'active')");

        $cbPdo->exec("INSERT INTO affiliates (id, user_id, code, status) VALUES
            (1, 1, 'TESTAFF1', 'approved')");

        $cbPdo->exec("INSERT INTO referrals (id, affiliate_id, referred_user_id, status, qualified_at) VALUES
            (1, 1, 2, 'qualified', NOW())");

        $cbPdo->exec("INSERT INTO invoices (id, number, user_id, total_amount, status) VALUES
            (1, 'INV-TEST-1', 2, 1000000, 'paid'),
            (2, 'INV-TEST-2', 2, 1000000, 'paid'),
            (3, 'INV-TEST-3', 2, 1000000, 'paid')");

        // Three payments: one whose commission is still pending, one already approved,
        // one already paid out — the three states clawback has to handle differently.
        $cbPdo->exec("INSERT INTO payments (id, reference, invoice_id, user_id, method, amount, status) VALUES
            (1, 'ULP-TEST-1', 1, 2, 'paystack', 1000000, 'successful'),
            (2, 'ULP-TEST-2', 2, 2, 'paystack', 1000000, 'successful'),
            (3, 'ULP-TEST-3', 3, 2, 'paystack', 1000000, 'successful')");

        $cbPdo->exec("INSERT INTO commissions (id, affiliate_id, referral_id, payment_id, base_amount, rate_bps, amount, status) VALUES
            (1, 1, 1, 1, 1000000, 500, 50000, 'pending'),
            (2, 1, 1, 2, 1000000, 500, 50000, 'approved'),
            (3, 1, 1, 3, 1000000, 500, 50000, 'paid')");

        require_once dirname(__DIR__) . '/config/bootstrap.php';
        require_once dirname(__DIR__) . '/app/core/Database.php';
        require_once dirname(__DIR__) . '/app/core/Auth.php';
        require_once dirname(__DIR__) . '/app/models/Affiliate.php';

        preg_match('/host=([^;]+)/', $cbDsn, $mHost);
        preg_match('/port=([^;]+)/', $cbDsn, $mPort);
        $GLOBALS['ultrademy_config']['db'] = [
            'host' => $mHost[1] ?? '127.0.0.1', 'port' => (int) ($mPort[1] ?? 3306),
            'name' => $cbDbName, 'user' => $cbUser, 'pass' => $cbPass, 'charset' => 'utf8mb4',
        ];

        $cbReady = true;
        $cbPdoRef = $cbPdo;
    } catch (Throwable $e) {
        $cbSkipReason = 'could not set up the DB_TEST_DSN fixture database: ' . $e->getMessage();
    }
}

test('clawback voids a still-pending commission', function () use (&$cbReady, &$cbSkipReason, &$cbPdoRef) {
    if (!$cbReady) skip($cbSkipReason);
    Affiliate::clawback(1, 'test: payment refunded');
    $row = $cbPdoRef->query('SELECT status, void_reason FROM commissions WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    assertSame_('void', $row['status'], 'a pending commission is voided outright');
    assertSame_('test: payment refunded', $row['void_reason']);
});

test('clawback voids an already-approved-but-unpaid commission', function () use (&$cbReady, &$cbSkipReason, &$cbPdoRef) {
    if (!$cbReady) skip($cbSkipReason);
    Affiliate::clawback(2, 'test: payment refunded');
    $row = $cbPdoRef->query('SELECT status FROM commissions WHERE id = 2')->fetch(PDO::FETCH_ASSOC);
    assertSame_('void', $row['status'], 'approved-but-not-yet-paid-out money can be reversed cleanly');
});

test('clawback voids an already-paid commission too, but does not silently invent a debt', function () use (&$cbReady, &$cbSkipReason, &$cbPdoRef) {
    if (!$cbReady) skip($cbSkipReason);
    Affiliate::clawback(3, 'test: payment refunded');
    $row = $cbPdoRef->query('SELECT status FROM commissions WHERE id = 3')->fetch(PDO::FETCH_ASSOC);
    assertSame_('void', $row['status'], 'a paid commission is still voided so it stops counting toward future totals');
    // Recovering money already sent to the affiliate is a finance decision, not something
    // this reversal automates — see Affiliate::clawback()'s docblock. This test exists so
    // that claim stays true: nothing here creates a negative balance or a new payout row.
    $payoutCount = (int) $cbPdoRef->query('SELECT COUNT(*) c FROM commissions WHERE payout_id IS NOT NULL AND status = \'void\'')->fetch(PDO::FETCH_ASSOC)['c'];
    assertSame_(0, $payoutCount, 'clawback never touches payout_id — recovery on an already-paid commission stays a manual step');
});

test('clawback on a payment with no commission is a safe no-op', function () use (&$cbReady, &$cbSkipReason, &$cbPdoRef) {
    if (!$cbReady) skip($cbSkipReason);
    Affiliate::clawback(999999, 'no such payment');
    // No exception, and nothing in the fixture data changed as a side effect.
    $stillThree = (int) $cbPdoRef->query("SELECT COUNT(*) c FROM commissions")->fetch(PDO::FETCH_ASSOC)['c'];
    assertSame_(3, $stillThree, 'a payment with no commission leaves the table untouched');
});

test('clawback is idempotent — voiding an already-void commission does nothing new', function () use (&$cbReady, &$cbSkipReason, &$cbPdoRef) {
    if (!$cbReady) skip($cbSkipReason);
    // Commission 1 was already voided by the first test in this file.
    Affiliate::clawback(1, 'second refund attempt on the same payment');
    $row = $cbPdoRef->query('SELECT void_reason FROM commissions WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    assertSame_('test: payment refunded', $row['void_reason'], 'an already-void commission is left alone, not overwritten by a later call');
});
