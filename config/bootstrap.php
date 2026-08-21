<?php
declare(strict_types=1);

/**
 * Loads .env (if present) and exposes a config() helper.
 * Kept deliberately small — we grow it as the app grows.
 */

$root = dirname(__DIR__);

// --- minimal .env loader -------------------------------------------------
$envFile = $root . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        $_ENV[$key] = $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

/**
 * Where this application lives, worked out from the request itself.
 *
 * Used only when APP_URL is absent. It exists because the base URL is the one setting
 * that silently breaks everything when it goes stale: same-app links are relative and
 * keep working, so the app *looks* fine right up until a login redirect built by
 * app_url() throws the user at a folder name that no longer exists.
 *
 * Deriving it from DOCUMENT_ROOT means renaming the project folder — ultra to
 * ultrademymain, say — needs no edit anywhere. An explicit APP_URL still wins, because
 * behind a real vhost or a proxy the filesystem no longer tells the truth about the URL.
 */
function ultrademy_detect_base_url(string $root): string
{
    // CLI: tests, cron, migrations. There is no request to read, and nothing that runs
    // here should be minting absolute URLs anyway.
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return 'http://localhost';
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';

    // HTTP_HOST is attacker-controlled. Keep host-shaped characters only, so a poisoned
    // header cannot smuggle a path or a second URL into every link on the page.
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) $_SERVER['HTTP_HOST']);
    if ($host === '') {
        return 'http://localhost';
    }

    $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $project = realpath($root);
    $prefix = '';
    if ($docRoot !== false && $project !== false) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $project = rtrim(str_replace('\\', '/', $project), '/');
        // Windows paths are case-insensitive; C:/xampp/htdocs and C:/XAMPP/htdocs are
        // the same directory and must not produce two different base URLs.
        if ($docRoot !== '' && stripos($project, $docRoot) === 0) {
            $prefix = substr($project, strlen($docRoot));
        }
    }

    return $scheme . '://' . $host . rtrim($prefix, '/');
}

// --- configuration -------------------------------------------------------
$GLOBALS['ultrademy_config'] = [
    'app' => [
        'name'  => env('APP_NAME', 'Ultrademy'),
        'env'   => env('APP_ENV', 'local'),
        'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
        'force_https' => filter_var(env('FORCE_HTTPS', 'false'), FILTER_VALIDATE_BOOL),
        'url'   => env('APP_URL') ?: ultrademy_detect_base_url($root),
        'root'  => $root,
    ],
    // Deliberately separate from app.url (docs/architecture/16-careers-portal.md §14) — the
    // careers portal is a second front controller with its own base URL, whether that's
    // still a path under the main app (path-based v1) or a real subdomain post-cutover.
    // Nothing in code should ever hard-code which of those two this currently is.
    'careers' => [
        'url' => env('CAREERS_URL') ?: (rtrim((string) (env('APP_URL') ?: ultrademy_detect_base_url($root)), '/') . '/careers'),
    ],
    // Same pattern as `careers` above: a third front controller with its own base URL
    // and its own session cookie (ultrademy_affiliate_session). An affiliate is always
    // an existing UltrAdemy account — this is not a separate identity system, just a
    // separate session boundary, exactly as careers already demonstrates.
    'affiliate' => [
        'url' => env('AFFILIATE_URL') ?: (rtrim((string) (env('APP_URL') ?: ultrademy_detect_base_url($root)), '/') . '/affiliate'),
    ],
    'db' => [
        'host'    => env('DB_HOST', '127.0.0.1'),
        'port'    => (int) env('DB_PORT', 3306),
        'name'    => env('DB_NAME', 'ultrademy'),
        'user'    => env('DB_USER', 'root'),
        'pass'    => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
];

/** Dot-notation config lookup: config('db.host') */
function config(string $path, mixed $default = null): mixed
{
    $value = $GLOBALS['ultrademy_config'];
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/**
 * Absolute URL into the MAIN app, built from app.url — never hard-code "/ultrademymain/..."
 * (docs/architecture/16-careers-portal.md §14: that prefix disappears the moment this runs
 * behind a real vhost). Only for cross-app links; same-app pages should stay relative,
 * exactly like shell.php's own "css/shell.css" already does.
 */
function app_url(string $path = ''): string
{
    return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
}

/** Same as app_url(), but into the careers portal — see the `careers.url` config entry above. */
function careers_url(string $path = ''): string
{
    return rtrim((string) config('careers.url'), '/') . '/' . ltrim($path, '/');
}

/** Same as app_url(), but into the affiliate portal — see the `affiliate.url` config entry above. */
function affiliate_url(string $path = ''): string
{
    return rtrim((string) config('affiliate.url'), '/') . '/' . ltrim($path, '/');
}

// --- error reporting -----------------------------------------------------
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Africa/Lagos');

// --- force HTTPS -----------------------------------------------------------
// Toggled from the Utilities page (Disable/Enable Force HTTPS); nothing to enforce for
// CLI scripts (migrations, cron, seeding), which have no request to redirect.
if (config('app.force_https') && PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if (!$isHttps) {
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) $_SERVER['HTTP_HOST']);
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
}

// --- autoloader ------------------------------------------------------------
// No namespaces yet — classes are looked up by convention across the three
// app/ subfolders. Simple on purpose; revisit if the app grows past this.
spl_autoload_register(function (string $class) use ($root): void {
    foreach (['core', 'models', 'controllers'] as $dir) {
        $path = "$root/app/$dir/$class.php";
        if (is_file($path)) {
            require $path;
            return;
        }
    }
});
