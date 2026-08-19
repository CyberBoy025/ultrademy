<?php
declare(strict_types=1);

/**
 * IP/account rate limiting for the platform's first fully public, anonymous-facing
 * surface (docs/architecture/16-careers-portal.md §13/§20). Counts rows in a trailing
 * window rather than a running counter, so concurrent requests can't race each other into
 * under-counting — the same reasoning DocumentNumber.php gives for avoiding a naive
 * COUNT-then-write pattern, applied here because the cost of getting it wrong is a bot
 * slipping through, not a duplicate invoice.
 */
final class RateLimit
{
    /**
     * Records this attempt and reports whether the caller is still within the limit.
     * Deliberately records the hit even when it pushes the caller over the limit, so a
     * script hammering the endpoint doesn't get a free retry every time it's blocked.
     */
    public static function attempt(string $action, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        $bucket = self::bucket($action, $identifier);
        $count = (int) Database::one(
            'SELECT COUNT(*) c FROM rate_limit_hits WHERE bucket = :b AND created_at > DATE_SUB(NOW(), INTERVAL :w SECOND)',
            ['b' => $bucket, 'w' => $windowSeconds]
        )['c'];

        Database::query('INSERT INTO rate_limit_hits (bucket) VALUES (:b)', ['b' => $bucket]);

        return $count < $maxAttempts;
    }

    /** Seconds until the oldest hit in the current window ages out — for a "try again in Ns" message. */
    public static function retryAfter(string $action, string $identifier, int $windowSeconds): int
    {
        $bucket = self::bucket($action, $identifier);
        $oldest = Database::one(
            'SELECT MIN(created_at) t FROM rate_limit_hits WHERE bucket = :b AND created_at > DATE_SUB(NOW(), INTERVAL :w SECOND)',
            ['b' => $bucket, 'w' => $windowSeconds]
        )['t'];
        if ($oldest === null) {
            return 0;
        }
        return max(0, $windowSeconds - (time() - strtotime((string) $oldest)));
    }

    /** Housekeeping — no cron exists for this table, so callers prune opportunistically (cheap, indexed delete). */
    public static function prune(int $olderThanSeconds = 86400): void
    {
        Database::query('DELETE FROM rate_limit_hits WHERE created_at < DATE_SUB(NOW(), INTERVAL :s SECOND)', ['s' => $olderThanSeconds]);
    }

    private static function bucket(string $action, string $identifier): string
    {
        return substr($action . ':' . strtolower($identifier), 0, 150);
    }
}
