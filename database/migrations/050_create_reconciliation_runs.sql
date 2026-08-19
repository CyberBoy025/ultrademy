-- §10. Exceptions land in an accountant queue and are NEVER auto-corrected —
-- auto-correcting a mismatch is how a bug quietly rewrites the books.
CREATE TABLE IF NOT EXISTS reconciliation_runs (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    period_start   DATE NOT NULL,
    period_end     DATE NOT NULL,
    checked_count  INT UNSIGNED NOT NULL DEFAULT 0,
    matched_count  INT UNSIGNED NOT NULL DEFAULT 0,
    exception_count INT UNSIGNED NOT NULL DEFAULT 0,
    exceptions     JSON NULL COMMENT 'list of {payment_id, reference, issue} — for a human to decide on',
    run_by         BIGINT UNSIGNED NULL COMMENT 'NULL = scheduled run',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reconciliation_user FOREIGN KEY (run_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
