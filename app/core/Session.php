<?php
declare(strict_types=1);

/** Starts the PHP session with sane, explicit cookie params. Call once per request. */
final class Session
{
    /**
     * @param string $name distinct cookie name per surface, so the careers portal
     *     (docs/architecture/16-careers-portal.md §7) never shares a login with the LMS —
     *     signing into one does not open a session in the other.
     */
    public static function start(string $name = 'ultrademy_session'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name($name);

        // A stale or foreign cookie under this name (leftover from a previous config,
        // a different app on localhost, etc.) can carry an id session_start() rejects
        // outright — PHP then only warns and continues without ever opening a session,
        // so every read/write of $_SESSION silently no-ops for the rest of the request.
        // Discard it up front so a fresh, valid id gets minted instead.
        if (isset($_COOKIE[$name]) && !preg_match('/^[a-zA-Z0-9,-]{22,250}$/', $_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            setcookie($name, '', time() - 42000, '/');
        }

        session_start();
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $out = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $out;
    }
}
