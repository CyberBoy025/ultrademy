CREATE TABLE IF NOT EXISTS audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id   BIGINT UNSIGNED NULL COMMENT 'NULL = system action',
    action          VARCHAR(100) NOT NULL,
    auditable_type  VARCHAR(100) NOT NULL,
    auditable_id    BIGINT UNSIGNED NOT NULL,
    old_values      JSON NULL,
    new_values      JSON NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(255) NULL,
    centre_id       BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_audit_auditable (auditable_type, auditable_id, created_at),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_logs_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
