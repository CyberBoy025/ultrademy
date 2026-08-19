<?php
declare(strict_types=1);

/** One CSRF token per session, checked on every state-changing POST (README §42). */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify(): bool
    {
        $sent = $_POST['_csrf'] ?? '';
        return is_string($sent) && $sent !== '' && hash_equals($_SESSION['_csrf'] ?? '', $sent);
    }

    /** Verifies the token or kills the request with a 403. (419 is non-standard and some SAPIs mangle it into a 500.) */
    public static function requireValid(): void
    {
        if (!self::verify()) {
            http_response_code(403);
            exit('Session expired — please go back and try again.');
        }
    }
}
