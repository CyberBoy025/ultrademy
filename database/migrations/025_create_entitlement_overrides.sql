CREATE TABLE IF NOT EXISTS entitlement_overrides (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    feature_id  BIGINT UNSIGNED NOT NULL,
    granted     TINYINT(1) NOT NULL COMMENT '1 = grant (comp/promo/corporate), 0 = revoke (sanction)',
    limit_value BIGINT UNSIGNED NULL COMMENT 'NULL = unlimited, when granted',
    expires_at  TIMESTAMP NULL DEFAULT NULL,
    reason      VARCHAR(255) NULL,
    granted_by  BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_feature_override (user_id, feature_id),
    CONSTRAINT fk_overrides_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_overrides_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE,
    CONSTRAINT fk_overrides_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
