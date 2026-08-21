-- Affiliates (README §25).
--
-- One affiliate record per user, never a separate account — §3's "one identity, many
-- relationships". The `affiliate` role is granted when the record is approved and revoked
-- if it is suspended, exactly as 03-rbac.md §2 describes for student and applicant.
CREATE TABLE IF NOT EXISTS affiliates (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    code                VARCHAR(20) NOT NULL COMMENT 'the referral code, shown in their link',
    status              ENUM('applied','under_review','approved','suspended','rejected') NOT NULL DEFAULT 'applied',
    -- Basis points, not a float: 2.5% is 250. No float ever touches the money path
    -- (05-finance-payments.md §1), and a commission rate is part of that path.
    commission_rate_bps SMALLINT UNSIGNED NOT NULL DEFAULT 500,
    payout_method       VARCHAR(40) NULL,
    payout_details      VARCHAR(255) NULL COMMENT 'bank account or wallet the affiliate nominates',
    motivation          TEXT NULL COMMENT 'what they wrote when applying',
    approved_by         BIGINT UNSIGNED NULL,
    approved_at         TIMESTAMP NULL DEFAULT NULL,
    decision_note       VARCHAR(255) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_affiliate_user (user_id),
    UNIQUE KEY uq_affiliate_code (code),
    KEY ix_affiliate_status (status),
    CONSTRAINT fk_affiliate_user     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_affiliate_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
