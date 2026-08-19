CREATE TABLE IF NOT EXISTS user_roles (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    role_id      BIGINT UNSIGNED NOT NULL,
    centre_id    BIGINT UNSIGNED NULL COMMENT 'NULL = global scope',
    granted_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    granted_by   BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_user_role_centre (user_id, role_id, centre_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_granted_by FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
