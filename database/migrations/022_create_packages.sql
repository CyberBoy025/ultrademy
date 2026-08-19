CREATE TABLE IF NOT EXISTS packages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(40) NOT NULL,
    name            VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NULL,
    price_amount    BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'minor units (kobo)',
    currency        CHAR(3) NOT NULL DEFAULT 'NGN',
    billing_period  ENUM('monthly','quarterly','annual','one_off') NOT NULL DEFAULT 'monthly',
    duration_days   SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    status          ENUM('draft','active','retired') NOT NULL DEFAULT 'draft',
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_packages_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
