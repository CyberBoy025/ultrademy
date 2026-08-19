CREATE TABLE IF NOT EXISTS subscriptions (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED NOT NULL,
    package_id    BIGINT UNSIGNED NOT NULL,
    status        ENUM('pending','active','expired','cancelled','void') NOT NULL DEFAULT 'pending',
    starts_at     TIMESTAMP NULL DEFAULT NULL,
    ends_at       TIMESTAMP NULL DEFAULT NULL,
    auto_renew    TINYINT(1) NOT NULL DEFAULT 0,
    cancelled_at  TIMESTAMP NULL DEFAULT NULL,
    activated_by  BIGINT UNSIGNED NULL COMMENT 'until Phase 9, activation is manual rather than payment-driven',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Decision 14 (one active subscription per user), enforced by the database rather
    -- than by application code: NULLs do not collide in a UNIQUE index, so only rows
    -- with status='active' are constrained.
    active_user_id BIGINT UNSIGNED AS (IF(status = 'active', user_id, NULL)) STORED,

    UNIQUE KEY uq_one_active_subscription (active_user_id),
    KEY ix_subscriptions_user (user_id, status),
    KEY ix_subscriptions_expiry (status, ends_at),
    CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE RESTRICT,
    CONSTRAINT fk_subscriptions_activated_by FOREIGN KEY (activated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
