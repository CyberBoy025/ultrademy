<?php
declare(strict_types=1);

/**
 * Applicant-facing notification inbox (brief §40) — scoped to the `recruitment` category
 * only. The underlying `notifications` table is shared platform-wide by design, but this
 * portal must never surface a person's unrelated LMS/finance notifications (brief §51/§52:
 * no unnecessary platform content on the careers frontend), so every query here filters to
 * `recruitment` explicitly rather than relying on there simply being nothing else to show.
 */
final class CareersNotificationController
{
    private const CATEGORY = 'recruitment';

    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function index(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/notifications/index', [
            'notifications' => Notify::inbox($userId, 50, self::CATEGORY),
            'unread' => Notify::unreadCount($userId, self::CATEGORY),
        ]);
        View::careersShell('notifications', 'Notifications', $main);
    }

    /** Marks read then forwards to wherever the notification pointed — mirrors NotificationController::open(). */
    public static function open(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $id = (int) ($_GET['id'] ?? 0);
        $row = Database::one(
            'SELECT * FROM notifications WHERE id = :id AND user_id = :u AND category = :c',
            ['id' => $id, 'u' => $userId, 'c' => self::CATEGORY]
        );
        if (!$row) {
            http_response_code(404);
            echo 'Notification not found.';
            return;
        }
        Notify::markRead($id, $userId);
        header('Location: ' . ($row['url'] ?: 'app.php?r=notifications'));
        exit;
    }

    public static function markAllRead(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $n = Notify::markAllRead((int) Auth::id(), self::CATEGORY);
        Session::flash('success', $n . ' notification(s) marked read.');
        header('Location: app.php?r=notifications');
        exit;
    }

    public static function preferences(): void
    {
        self::requireLogin();
        $all = Notify::preferences((int) Auth::id());
        $main = View::render('careers/notifications/preferences', [
            'channels' => $all[self::CATEGORY] ?? ['in_app' => true, 'email' => true],
        ]);
        View::careersShell('notifications', 'Notification Settings', $main);
    }

    /** Only ever touches the `recruitment` category — a careers-portal form must never reset unrelated LMS preferences. */
    public static function savePreferences(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $submitted = $_POST['pref'] ?? [];
        foreach (['in_app', 'email'] as $channel) {
            Notify::setPreference($userId, self::CATEGORY, $channel, isset($submitted[$channel]));
        }
        Session::flash('success', 'Notification settings saved.');
        header('Location: app.php?r=notifications.preferences');
        exit;
    }
}
