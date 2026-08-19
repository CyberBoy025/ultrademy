-- Decision 20: receipts ARE sequentially numbered for tax purposes.
CREATE TABLE IF NOT EXISTS receipts (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number      VARCHAR(30) NOT NULL COMMENT 'RCP-YYMM-seq',
    payment_id  BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    amount      BIGINT UNSIGNED NOT NULL,
    currency    CHAR(3) NOT NULL DEFAULT 'NGN',
    issued_by   BIGINT UNSIGNED NULL COMMENT 'NULL = issued by the system on webhook confirmation',
    issued_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_receipts_number (number),
    -- One receipt per payment. A second receipt for the same money is a tax problem.
    UNIQUE KEY uq_receipts_payment (payment_id),
    CONSTRAINT fk_receipts_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
    CONSTRAINT fk_receipts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_receipts_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
