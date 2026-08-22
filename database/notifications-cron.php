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
 * Transport is `mail_transport` in Settings (empty by default — deliberately not PHP's
 * mail(), which silently succeeds on a box with no MTA and would make the queue *look*
 * delivered). Set it to "smtp" and fill in SMTP_HOST/SMTP_USER/SMTP_PASS in .env (see
 * Mailer::configured()) to actually send; until then this job reports the backlog and
 * leaves it untouched.
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
        if ($transport !== 'smtp') {
            throw new RuntimeException("Transport '$transport' is configured but not implemented.");
        }
        if (!Mailer::configured()) {
            throw new RuntimeException('SMTP transport selected but SMTP_HOST/SMTP_USER/SMTP_PASS are not fully set in .env.');
        }
        $result = Mailer::send(
            (string) $row['email'],
            (string) $row['name'],
            (string) $row['title'],
            Mailer::notificationHtml((string) $row['title'], $row['body'] ?? null, $row['url'] ?? null)
        );
        if ($result !== true) {
            throw new RuntimeException($result);
        }
        Database::query('UPDATE notifications SET sent_at = NOW() WHERE id = :id', ['id' => $row['id']]);
        $sent++;
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
