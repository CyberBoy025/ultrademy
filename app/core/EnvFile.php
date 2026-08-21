<?php
declare(strict_types=1);

/**
 * Rewrites a single KEY=value line in the project's .env file, preserving everything
 * else byte-for-byte. Backs the Utilities page's Disable App Debug / Disable Force HTTPS
 * toggles (docs/architecture — those are .env-level concerns, not settings-table rows).
 *
 * Deliberately allowlisted: this must never become a general "write anything to .env"
 * tool, since .env also holds DB credentials.
 */
final class EnvFile
{
    private const ALLOWED_KEYS = ['APP_DEBUG', 'FORCE_HTTPS'];

    public static function set(string $key, string $value): void
    {
        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            throw new InvalidArgumentException("EnvFile may not write key: $key");
        }

        $path = dirname(__DIR__, 2) . '/.env';
        $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];

        $found = false;
        foreach ($lines as &$line) {
            if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', $line)) {
                $line = "$key=$value";
                $found = true;
                break;
            }
        }
        unset($line);
        if (!$found) {
            $lines[] = "$key=$value";
        }

        file_put_contents($path, implode("\n", $lines) . "\n", LOCK_EX);

        // Take effect for the rest of THIS request too, not just the next one.
        $_ENV[$key] = $value;
    }
}
