CREATE TABLE IF NOT EXISTS permissions (
    id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code     VARCHAR(100) NOT NULL,
    module   VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
