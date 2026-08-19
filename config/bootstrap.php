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

// --- configuration -------------------------------------------------------
$GLOBALS['ultrademy_config'] = [
    'app' => [
        'name'  => env('APP_NAME', 'Ultrademy'),
        'env'   => env('APP_ENV', 'local'),
        'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
        'url'   => env('APP_URL', 'http://localhost'),
        'root'  => $root,
    ],
    // Deliberately separate from app.url (docs/architecture/16-careers-portal.md §14) — the
    // careers portal is a second front controller with its own base URL, whether that's
    // still a path under the main app (path-based v1) or a real subdomain post-cutover.
    // Nothing in code should ever hard-code which of those two this currently is.
    'careers' => [
        'url' => env('CAREERS_URL', 'http://localhost/ultra/public/careers'),
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
 * Absolute URL into the MAIN app, built from app.url — never hard-code "/ultra/public/..."
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

// --- error reporting -----------------------------------------------------
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Africa/Lagos');

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
