<?php
declare(strict_types=1);

/**
 * Expires subscriptions whose ends_at has passed.
 *
 * 04-subscriptions-entitlements.md §7: "Expiry is a scheduled job, not a computed
 * property, so that expiry EVENTS fire and notifications go out." Decision 12 takes
 * the default of no grace period — a hard stop at ends_at.
 *
 * Run from Task Scheduler (Windows) or cron, once daily:
 *   php C:\xampp\htdocs\ultra\database\expire-subscriptions.php
 *
 * Safe to run repeatedly — it only touches rows that are active AND past ends_at.
 */

require __DIR__ . '/../config/bootstrap.php';

$expired = Subscription::expireDue();

foreach ($expired as $row) {
    // Audit as a system action: Auth::id() is null on CLI, which the audit_logs
    // actor_user_id column already allows and reads as "system".
    Audit::log(
        $row['cancelled_at'] !== null ? 'subscription.cancelled_at_period_end' : 'subscription.expired',
        'subscriptions',
        (int) $row['id'],
        ['status' => 'active'],
        ['status' => $row['cancelled_at'] !== null ? 'cancelled' : 'expired']
    );
    // TODO (Phase 10 — Communication): fire the expiry notification here. The
    // notifications table does not exist yet, so nothing is queued rather than
    // pretending a message was sent.
}

printf(
    "%s — %d subscription(s) expired.\n",
    date('Y-m-d H:i:s'),
    count($expired)
);
