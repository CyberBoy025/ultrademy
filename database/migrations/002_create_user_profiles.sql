CREATE TABLE IF NOT EXISTS user_profiles (
    user_id         BIGINT UNSIGNED PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    date_of_birth   DATE NULL,
    gender          VARCHAR(20) NULL,
    address_line    VARCHAR(255) NULL,
    city            VARCHAR(100) NULL,
    state           VARCHAR(100) NULL,
    country         VARCHAR(100) NULL DEFAULT 'Nigeria',
    photo_path      VARCHAR(255) NULL,
    completion_pct  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
