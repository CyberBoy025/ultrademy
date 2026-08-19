CREATE TABLE IF NOT EXISTS users (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email             VARCHAR(255) NOT NULL,
    phone             VARCHAR(32) NULL,
    password_hash     VARCHAR(255) NOT NULL,
    status            ENUM('pending','active','suspended','closed') NOT NULL DEFAULT 'pending',
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    phone_verified_at TIMESTAMP NULL DEFAULT NULL,
    last_login_at     TIMESTAMP NULL DEFAULT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
