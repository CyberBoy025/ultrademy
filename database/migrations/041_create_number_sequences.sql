-- Gap-free-ish document numbering for invoices and receipts.
--
-- 05-finance-payments.md §3: "generated inside the same transaction as the insert with a
-- row lock. Gaps are acceptable; duplicates are not." Counting existing rows to derive
-- the next number is exactly the race that produces duplicates under concurrency, so the
-- counter lives here and is taken with SELECT ... FOR UPDATE.

CREATE TABLE IF NOT EXISTS number_sequences (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prefix     VARCHAR(10) NOT NULL COMMENT 'INV / RCP',
    period     CHAR(4) NOT NULL COMMENT 'YYMM — sequence resets each month',
    next_value INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_sequence (prefix, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
