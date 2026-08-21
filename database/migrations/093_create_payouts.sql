-- Money actually sent to an affiliate. Created before commissions so a commission can
-- carry its payout id.
CREATE TABLE IF NOT EXISTS affiliate_payouts (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference    VARCHAR(30) NOT NULL,
    affiliate_id BIGINT UNSIGNED NOT NULL,
    amount       BIGINT UNSIGNED NOT NULL COMMENT 'minor units',
    currency     CHAR(3) NOT NULL DEFAULT 'NGN',
    method       VARCHAR(40) NULL,
    bank_reference VARCHAR(120) NULL COMMENT 'the transfer reference from the bank',
    status       ENUM('requested','processing','paid','failed') NOT NULL DEFAULT 'requested',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at      TIMESTAMP NULL DEFAULT NULL,
    processed_by BIGINT UNSIGNED NULL,
    note         VARCHAR(255) NULL,
    UNIQUE KEY uq_payout_reference (reference),
    KEY ix_payout_affiliate (affiliate_id, status),
    CONSTRAINT fk_payout_affiliate FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    CONSTRAINT fk_payout_processor FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
