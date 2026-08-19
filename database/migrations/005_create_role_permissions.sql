CREATE TABLE IF NOT EXISTS role_permissions (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id        BIGINT UNSIGNED NOT NULL,
    permission_id  BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_role_permission (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
