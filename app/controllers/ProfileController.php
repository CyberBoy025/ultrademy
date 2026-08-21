<?php
declare(strict_types=1);

/**
 * Every signed-in user's own account settings — name, phone, profile photo, plus a
 * link into the notification preferences NotificationController already provides.
 * identity.profile.update is ○ (own records only) for every role in 03-rbac.md §5, so
 * there is deliberately no permission check here beyond being signed in — the same
 * reasoning SubscriptionController::mine() and NotificationController::preferences()
 * already rely on.
 */
final class ProfileController
{
    private const SUBDIR = 'avatars';
    private const MAX_PHOTO_BYTES = 3 * 1024 * 1024;

    public static function edit(): void
    {
        $main = View::render('profile/edit', [
            'user' => Auth::user(),
        ]);
        View::shell('profile', 'My Profile', $main);
    }

    public static function update(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();

        $error = User::updateOwnProfile(
            $userId,
            trim((string) ($_POST['first_name'] ?? '')),
            trim((string) ($_POST['last_name'] ?? '')),
            trim((string) ($_POST['phone'] ?? '')) ?: null
        );

        if ($error === '' && !empty($_FILES['photo']['name'])) {
            $result = Upload::store($_FILES['photo'], self::SUBDIR, Upload::IMAGE_TYPES, self::MAX_PHOTO_BYTES);
            if (is_string($result)) {
                $error = $result;
            } else {
                // The old file is orphaned the moment the DB row stops pointing at it —
                // delete it before the new stored_name overwrites the column.
                $old = Auth::user()['photo_path'] ?? null;
                User::updatePhoto($userId, $result['stored_name']);
                if ($old) {
                    Upload::delete(self::SUBDIR, $old);
                }
            }
        }

        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Profile updated.' : $error);
        header('Location: app.php?r=profile');
        exit;
    }

    /**
     * Streams the signed-in user's own photo — never anyone else's (see class
     * docblock). Deliberately not Upload::stream(): that helper sends
     * Content-Disposition: attachment, correct for a document download, wrong for an
     * avatar an <img> tag is meant to render inline.
     */
    public static function photo(): void
    {
        $user = Auth::user();
        $stored = $user['photo_path'] ?? null;
        $path = $stored ? Upload::dir(self::SUBDIR) . '/' . $stored : null;
        if (!$path || !is_file($path)) {
            http_response_code(404);
            echo 'No photo set.';
            return;
        }
        $ext = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
