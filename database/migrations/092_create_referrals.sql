-- Who referred whom.
--
-- `referred_user_id` is UNIQUE across the whole table, not per affiliate. A person can be
-- referred once, ever (02-data-model.md §8). Without this, the obvious fraud is
-- re-attributing an existing user to a new affiliate — or to yourself — and collecting
-- again on someone who was already a customer.
CREATE TABLE IF NOT EXISTS referrals (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    affiliate_id     BIGINT UNSIGNED NOT NULL,
    referred_user_id BIGINT UNSIGNED NOT NULL,
    landed_at        TIMESTAMP NULL DEFAULT NULL COMMENT 'when the link was first followed',
    registered_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    qualified_at     TIMESTAMP NULL DEFAULT NULL COMMENT 'set by the first qualifying payment',
    status           ENUM('pending','qualified','void') NOT NULL DEFAULT 'pending',
    void_reason      VARCHAR(255) NULL,
    UNIQUE KEY uq_referral_user (referred_user_id),
    KEY ix_referral_affiliate (affiliate_id, status),
    CONSTRAINT fk_referral_affiliate FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    CONSTRAINT fk_referral_user      FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
