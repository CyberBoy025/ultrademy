<?php
declare(strict_types=1);

/**
 * Nightly finance housekeeping.
 *
 *   php C:\xampp\htdocs\ultra\database\finance-cron.php
 *
 * Two jobs 05-finance-payments.md asks for as scheduled work rather than computed
 * properties, so that events fire and someone can be told:
 *
 *   §3  invoices whose due date has passed become `overdue`
 *   §10 a reconciliation run over the last 7 days, whose exceptions land in the
 *       accountant's queue — never auto-corrected
 *
 * Safe to run repeatedly. Runs as the system (no Auth::id()), which audit_logs already
 * records as a null actor.
 */

require __DIR__ . '/../config/bootstrap.php';

$overdue = Invoice::markOverdue();
printf("%s — %d invoice(s) marked overdue.\n", date('Y-m-d H:i:s'), $overdue);

$from = date('Y-m-d', strtotime('-7 days'));
$to   = date('Y-m-d');
$result = Reconciliation::run($from, $to, null);

printf(
    "%s — reconciliation %s to %s: %d checked, %d matched, %d exception(s).\n",
    date('Y-m-d H:i:s'), $from, $to,
    $result['checked'], $result['matched'], $result['exceptions']
);

if ($result['exceptions'] > 0) {
    echo "  Exceptions are listed on the Financial Reports page for an accountant to decide on.\n";
}
