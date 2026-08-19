<?php
declare(strict_types=1);

/**
 * Drains the queued email notifications.
 *
 *   php C:\xampp\htdocs\ultra\database\notifications-cron.php
 *
 * 06-api-notifications.md §2: "All notifications queue. Nothing user-facing waits on
 * SMTP." In-app rows are written immediately by Notify::send(); email rows sit here with
 * sent_at NULL until this job runs.
 *
 * NO MAIL TRANSPORT IS CONFIGURED. Rather than call PHP's mail() — which silently
 * succeeds on a machine with no MTA and would make the queue *look* delivered — this job
 * reports the backlog and leaves it untouched. Set `mail_transport` in Settings and
 * implement the send below when a provider is chosen (Decision: still open).
 *
 * Retries: 3 attempts with backoff, then the row is marked failed so an administrator can
 * see it. A silently dropped admission notice is a real-world problem (§2).
 */

require __DIR__ . '/../config/bootstrap.php';

const MAX_ATTEMPTS = 3;

$transport = trim((string) (Setting::get('mail_transport', '') ?? ''));

$pending = Database::all(
    "SELECT n.*, u.email, CONCAT(p.first_name,' ',p.last_name) AS name
     FROM notifications n
     JOIN users u ON u.id = n.user_id
     LEFT JOIN user_profiles p ON p.user_id = u.id
     WHERE n.channel = 'email' AND n.sent_at IS NULL AND n.failed_at IS NULL
       AND n.attempts < " . MAX_ATTEMPTS . "
     ORDER BY n.created_at LIMIT 200"
);

if ($transport === '') {
    printf(
        "%s — %d email notification(s) queued, but no mail transport is configured.\n",
        date('Y-m-d H:i:s'),
        count($pending)
    );
    echo "  Nothing was sent and nothing was marked sent. Set `mail_transport` in Settings\n";
    echo "  and implement the send in this file once a provider is chosen.\n";
    exit(0);
}

$sent = $failed = 0;
foreach ($pending as $row) {
    Database::query('UPDATE notifications SET attempts = attempts + 1 WHERE id = :id', ['id' => $row['id']]);

    try {
        // Deliberately not implemented: wiring mail() here would report success on a box
        // with no MTA. Whoever adds a provider implements it at this point.
        throw new RuntimeException("Transport '$transport' is configured but not implemented.");
    } catch (Throwable $e) {
        $attempts = ((int) $row['attempts']) + 1;
        Database::query(
            'UPDATE notifications SET last_error = :e, failed_at = :f WHERE id = :id',
            [
                'e' => mb_substr($e->getMessage(), 0, 255),
                'f' => $attempts >= MAX_ATTEMPTS ? date('Y-m-d H:i:s') : null,
                'id' => $row['id'],
            ]
        );
        $failed++;
    }
}

printf("%s — %d sent, %d failed, %d remaining.\n", date('Y-m-d H:i:s'), $sent, $failed, max(0, count($pending) - $sent - $failed));
