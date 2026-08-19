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

// --- error reporting -----------------------------------------------------
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
