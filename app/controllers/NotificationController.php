<?php
declare(strict_types=1);

/** In-app inbox, preferences, and announcements (README §37, §24). */
final class NotificationController
{
    public static function index(): void
    {
        $userId = (int) Auth::id();
        $main = View::render('notifications/index', [
            'notifications' => Notify::inbox($userId),
            'unread' => Notify::unreadCount($userId),
        ]);
        View::shell('notifications', 'Notifications', $main);
    }

    /** Marks read then forwards to wherever the notification pointed. */
    public static function open(): void
    {
        $userId = (int) Auth::id();
        $id = (int) ($_GET['id'] ?? 0);
        $row = Database::one('SELECT * FROM notifications WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $userId]);
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
        Csrf::requireValid();
        $n = Notify::markAllRead((int) Auth::id());
        Session::flash('success', $n . ' notification(s) marked read.');
        header('Location: app.php?r=notifications');
        exit;
    }

    public static function preferences(): void
    {
        $main = View::render('notifications/preferences', [
            'preferences' => Notify::preferences((int) Auth::id()),
            'locked' => Notify::LOCKED_CATEGORIES,
        ]);
        View::shell('notifications', 'Notification Settings', $main);
    }

    public static function savePreferences(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $submitted = $_POST['pref'] ?? [];

        foreach (Notify::CATEGORIES as $category) {
            if (in_array($category, Notify::LOCKED_CATEGORIES, true)) {
                continue;
            }
            foreach (['in_app', 'email'] as $channel) {
                Notify::setPreference($userId, $category, $channel, isset($submitted[$category][$channel]));
            }
        }
        Session::flash('success', 'Notification settings saved.');
        header('Location: app.php?r=notifications.preferences');
        exit;
    }

    // --------------------------------------------------------------- announcements

    public static function announcements(): void
    {
        Auth::requirePermission('comms.announcement.publish');
        $main = View::render('notifications/announcements', [
            'announcements' => Database::all(
                "SELECT a.*, c.name AS centre_name, co.name AS cohort_name,
                        CONCAT(pr.first_name,' ',pr.last_name) AS publisher_name
                 FROM announcements a
                 LEFT JOIN centres c ON c.id = a.centre_id
                 LEFT JOIN cohorts co ON co.id = a.cohort_id
                 LEFT JOIN user_profiles pr ON pr.user_id = a.published_by
                 ORDER BY a.published_at DESC LIMIT 50"
            ),
            'centres' => Centre::all(Auth::scopeCentres('comms.announcement.publish')),
            'cohorts' => Cohort::all(Auth::scopeCentres('comms.announcement.publish')),
        ]);
        View::shell('announcements', 'Announcements', $main);
    }

    public static function publishAnnouncement(): void
    {
        Auth::requirePermission('comms.announcement.publish');
        Csrf::requireValid();

        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $audience = in_array($_POST['audience'] ?? '', ['all', 'students', 'applicants', 'staff', 'centre', 'cohort'], true)
            ? $_POST['audience'] : 'all';

        if ($title === '' || $body === '') {
            Session::flash('error', 'An announcement needs a title and a body.');
            header('Location: app.php?r=announcements');
            exit;
        }

        $centreId = ($_POST['centre_id'] ?? '') !== '' ? (int) $_POST['centre_id'] : null;
        $cohortId = ($_POST['cohort_id'] ?? '') !== '' ? (int) $_POST['cohort_id'] : null;
        $recipients = Notify::audience($audience, $centreId, $cohortId);

        Database::query(
            'INSERT INTO announcements (title, body, audience, centre_id, cohort_id, recipient_count, published_by)
             VALUES (:t,:b,:a,:centre,:cohort,:n,:by)',
            [
                't' => $title, 'b' => $body, 'a' => $audience, 'centre' => $centreId,
                'cohort' => $cohortId, 'n' => count($recipients), 'by' => Auth::id(),
            ]
        );
        $announcementId = Database::lastInsertId();

        // Announcements are `operations`, so a user who has muted operational email still
        // gets the in-app copy — the platform stays the complete record.
        Notify::sendMany(
            $recipients,
            'announcement.published',
            'operations',
            $title,
            mb_substr($body, 0, 300),
            'app.php?r=notifications'
        );

        Audit::log('announcement.published', 'announcements', $announcementId, null, [
            'audience' => $audience, 'recipients' => count($recipients),
        ], $centreId);

        Session::flash('success', sprintf('Announcement sent to %d recipient(s).', count($recipients)));
        header('Location: app.php?r=announcements');
        exit;
    }
}
