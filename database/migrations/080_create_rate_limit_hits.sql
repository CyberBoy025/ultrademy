-- Generic rate limiting (docs/architecture/16-careers-portal.md §13). One row per attempt;
-- RateLimit::attempt() counts rows in a bucket within a trailing window rather than
-- maintaining a running counter, so concurrent requests can never under-count each other.
-- `bucket` is "action:identifier" — e.g. "careers.login:203.0.113.5" or
-- "careers.login:person@example.com" — so IP-based and account-based limits share one table.
CREATE TABLE IF NOT EXISTS rate_limit_hits (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bucket     VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_rate_limit_bucket_time (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
