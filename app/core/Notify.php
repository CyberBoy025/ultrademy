<?php
declare(strict_types=1);

/**
 * The notification engine (README §37, 06-api-notifications.md §2).
 *
 * Four responsibilities kept separate, as the architecture asks: a domain event decides
 * WHEN, a call to Notify decides WHO and WHAT, `category` decides whether a user may
 * switch it off, and `channel` decides HOW.
 *
 * Rules implemented here:
 *
 *   - Every notification lands IN-APP regardless of preferences, so the platform is
 *     always the complete record. Preferences gate email/SMS only.
 *   - Some categories cannot be switched off at all: security, payment and admission
 *     decisions. A missed admission notice is a real-world problem.
 *   - Floods collapse. More than 5 of one type for one user within an hour increments a
 *     digest counter instead of creating another row — otherwise marking a register of
 *     200 absent students sends 200 messages.
 */
final class Notify
{
    /** Categories a user may never switch off (§2). */
    public const LOCKED_CATEGORIES = ['security', 'payment', 'admission'];

    public const CATEGORIES = ['security', 'payment', 'admission', 'learning', 'operations', 'general', 'recruitment'];

    private const DIGEST_THRESHOLD = 5;
    private const DIGEST_WINDOW_MINUTES = 60;

    /**
     * Sends one notification to one user.
     *
     * @param array<string,mixed> $data
     * @return int|null the notification id, or null if it was folded into a digest
     */
    public static function send(
        int $userId,
        string $type,
        string $category,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): ?int {
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'general';
        }

        // Flood control before anything is written.
        $existing = Database::one(
            'SELECT id, digest_count FROM notifications
             WHERE user_id = :u AND type = :t AND channel = \'in_app\'
               AND created_at > DATE_SUB(NOW(), INTERVAL :mins MINUTE)
             ORDER BY created_at DESC LIMIT 1',
            ['u' => $userId, 't' => $type, 'mins' => self::DIGEST_WINDOW_MINUTES]
        );
        if ($existing !== null) {
            $recent = (int) Database::one(
                'SELECT COUNT(*) c FROM notifications
                 WHERE user_id = :u AND type = :t AND channel = \'in_app\'
                   AND created_at > DATE_SUB(NOW(), INTERVAL :mins MINUTE)',
                ['u' => $userId, 't' => $type, 'mins' => self::DIGEST_WINDOW_MINUTES]
            )['c'];

            if ($recent >= self::DIGEST_THRESHOLD) {
                Database::query(
                    'UPDATE notifications
                     SET digest_count = digest_count + 1, read_at = NULL, created_at = NOW()
                     WHERE id = :id',
                    ['id' => $existing['id']]
                );
                return null;
            }
        }

        // In-app always.
        Database::query(
            'INSERT INTO notifications (user_id, type, category, title, body, url, data, channel, sent_at)
             VALUES (:u,:t,:c,:title,:body,:url,:data,\'in_app\',NOW())',
            [
                'u' => $userId, 't' => $type, 'c' => $category, 'title' => $title,
                'body' => $body, 'url' => $url, 'data' => $data ? json_encode($data) : null,
            ]
        );
        $id = Database::lastInsertId();

        // Email is queued only if the user allows it. Nothing is sent inline — see
        // notifications-cron.php.
        if (self::channelAllowed($userId, $category, 'email')) {
            Database::query(
                'INSERT INTO notifications (user_id, type, category, title, body, url, data, channel)
                 VALUES (:u,:t,:c,:title,:body,:url,:data,\'email\')',
                [
                    'u' => $userId, 't' => $type, 'c' => $category, 'title' => $title,
                    'body' => $body, 'url' => $url, 'data' => $data ? json_encode($data) : null,
                ]
            );
        }

        return $id;
    }

    /** @param array<int,int> $userIds */
    public static function sendMany(array $userIds, string $type, string $category, string $title, ?string $body = null, ?string $url = null, array $data = []): int
    {
        $sent = 0;
        foreach (array_unique($userIds) as $userId) {
            self::send((int) $userId, $type, $category, $title, $body, $url, $data);
            $sent++;
        }
        return $sent;
    }

    /** Locked categories ignore preferences entirely. */
    public static function channelAllowed(int $userId, string $category, string $channel): bool
    {
        if (in_array($category, self::LOCKED_CATEGORIES, true)) {
            return true;
        }
        $row = Database::one(
            'SELECT enabled FROM notification_preferences WHERE user_id = :u AND category = :c AND channel = :ch',
            ['u' => $userId, 'c' => $category, 'ch' => $channel]
        );
        return $row === null || (int) $row['enabled'] === 1; // absent row = on
    }

    // ------------------------------------------------------------------- inbox

    /**
     * $category scopes the inbox to one category — used by the careers portal so an
     * applicant's recruitment inbox never surfaces their unrelated LMS/finance
     * notifications (brief §51: don't expose unnecessary platform content on the
     * careers frontend), even though every notification lives in this one shared table.
     */
    public static function inbox(int $userId, int $limit = 50, ?string $category = null): array
    {
        $limit = max(1, min(200, $limit));
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND channel = 'in_app'";
        $params = [$userId];
        if ($category !== null) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        return Database::query($sql, $params)->fetchAll();
    }

    public static function unreadCount(?int $userId = null, ?string $category = null): int
    {
        $userId ??= Auth::id();
        if ($userId === null) {
            return 0;
        }
        $sql = "SELECT COUNT(*) c FROM notifications WHERE user_id = :u AND channel = 'in_app' AND read_at IS NULL";
        $params = ['u' => $userId];
        if ($category !== null) {
            $sql .= ' AND category = :c';
            $params['c'] = $category;
        }
        return (int) Database::one($sql, $params)['c'];
    }

    public static function markRead(int $notificationId, int $userId): void
    {
        Database::query(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :u AND read_at IS NULL',
            ['id' => $notificationId, 'u' => $userId]
        );
    }

    public static function markAllRead(int $userId, ?string $category = null): int
    {
        $sql = "UPDATE notifications SET read_at = NOW() WHERE user_id = :u AND channel = 'in_app' AND read_at IS NULL";
        $params = ['u' => $userId];
        if ($category !== null) {
            $sql .= ' AND category = :c';
            $params['c'] = $category;
        }
        return Database::query($sql, $params)->rowCount();
    }

    // ------------------------------------------------------------- preferences

    /** @return array<string,array<string,bool>> category => channel => enabled */
    public static function preferences(int $userId): array
    {
        $rows = Database::all('SELECT category, channel, enabled FROM notification_preferences WHERE user_id = :u', ['u' => $userId]);
        $set = [];
        foreach ($rows as $r) {
            $set[$r['category']][$r['channel']] = (int) $r['enabled'] === 1;
        }
        $out = [];
        foreach (self::CATEGORIES as $category) {
            foreach (['in_app', 'email'] as $channel) {
                $out[$category][$channel] = $set[$category][$channel] ?? true;
            }
        }
        return $out;
    }

    public static function setPreference(int $userId, string $category, string $channel, bool $enabled): void
    {
        if (in_array($category, self::LOCKED_CATEGORIES, true)) {
            return; // cannot be switched off — silently ignored rather than pretending
        }
        Database::query(
            'INSERT INTO notification_preferences (user_id, category, channel, enabled)
             VALUES (:u,:c,:ch,:e) ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)',
            ['u' => $userId, 'c' => $category, 'ch' => $channel, 'e' => $enabled ? 1 : 0]
        );
    }

    // ------------------------------------------------------------- audiences

    /** Resolves an announcement audience to user ids. */
    public static function audience(string $audience, ?int $centreId, ?int $cohortId): array
    {
        $sql = match ($audience) {
            'students' => "SELECT DISTINCT user_id FROM enrolments WHERE status IN ('active','completed')",
            'applicants' => "SELECT DISTINCT user_id FROM applications WHERE status IN ('submitted','under_review')",
            'staff' => "SELECT DISTINCT ur.user_id FROM user_roles ur JOIN roles r ON r.id = ur.role_id
                        WHERE r.code NOT IN ('student','applicant','affiliate')",
            'centre' => "SELECT DISTINCT user_id FROM enrolments WHERE centre_id = :centre AND status = 'active'",
            'cohort' => 'SELECT DISTINCT user_id FROM enrolments WHERE cohort_id = :cohort',
            default => "SELECT id AS user_id FROM users WHERE status = 'active'",
        };
        $params = [];
        if ($audience === 'centre') {
            $params['centre'] = $centreId;
        }
        if ($audience === 'cohort') {
            $params['cohort'] = $cohortId;
        }
        return array_map('intval', array_column(Database::all($sql, $params), 'user_id'));
    }
}
