CREATE TABLE IF NOT EXISTS features (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(60) NOT NULL COMMENT 'a capability, never a package tier or UI element',
    name        VARCHAR(120) NOT NULL,
    module      VARCHAR(50) NOT NULL,
    limit_type  ENUM('none','count','bytes') NOT NULL DEFAULT 'none',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_features_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
