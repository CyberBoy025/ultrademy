-- Append-only audit trail (brief §18) — every transition writes a row here, mirroring the
-- discipline `Audit.php` already applies platform-wide.
CREATE TABLE IF NOT EXISTS job_application_status_history (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_application_id   BIGINT UNSIGNED NOT NULL,
    from_status          VARCHAR(20) NULL,
    to_status            VARCHAR(20) NOT NULL,
    changed_by           BIGINT UNSIGNED NULL,
    note                 VARCHAR(255) NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_status_history_application (job_application_id, created_at),
    CONSTRAINT fk_status_history_application FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
