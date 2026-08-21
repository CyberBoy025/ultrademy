-- What an affiliate has earned.
--
-- `payment_id` is UNIQUE: one payment can generate at most one commission, ever. That is
-- the idempotency guard for the earning hook — a replayed webhook, a double-clicked
-- verify button, or a re-run reconciliation cannot pay an affiliate twice.
--
-- `rate_bps` and `base_amount` are snapshotted rather than read from the affiliate at
-- display time. If the rate is renegotiated next month, commissions already earned must
-- not silently change value.
CREATE TABLE IF NOT EXISTS commissions (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    affiliate_id BIGINT UNSIGNED NOT NULL,
    referral_id  BIGINT UNSIGNED NOT NULL,
    payment_id   BIGINT UNSIGNED NOT NULL,
    base_amount  BIGINT UNSIGNED NOT NULL COMMENT 'the payment this was calculated from',
    rate_bps     SMALLINT UNSIGNED NOT NULL COMMENT 'rate at the moment of earning',
    amount       BIGINT UNSIGNED NOT NULL COMMENT 'minor units',
    currency     CHAR(3) NOT NULL DEFAULT 'NGN',
    status       ENUM('pending','approved','paid','void') NOT NULL DEFAULT 'pending',
    payout_id    BIGINT UNSIGNED NULL,
    approved_by  BIGINT UNSIGNED NULL,
    approved_at  TIMESTAMP NULL DEFAULT NULL,
    void_reason  VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_commission_payment (payment_id),
    KEY ix_commission_affiliate (affiliate_id, status),
    KEY ix_commission_payout (payout_id),
    CONSTRAINT fk_commission_affiliate FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_referral  FOREIGN KEY (referral_id) REFERENCES referrals(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_payment   FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_payout    FOREIGN KEY (payout_id) REFERENCES affiliate_payouts(id) ON DELETE SET NULL,
    CONSTRAINT fk_commission_approver  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
