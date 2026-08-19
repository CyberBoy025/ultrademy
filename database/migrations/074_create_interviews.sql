CREATE TABLE IF NOT EXISTS interviews (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_application_id   BIGINT UNSIGNED NOT NULL,
    scheduled_at         DATETIME NULL,
    type                 ENUM('physical','online','telephone') NOT NULL DEFAULT 'online',
    location             VARCHAR(255) NULL,
    meeting_link         VARCHAR(255) NULL,
    instructions         TEXT NULL,
    status               ENUM('scheduled','completed','cancelled','rescheduled') NOT NULL DEFAULT 'scheduled',
    created_by           BIGINT UNSIGNED NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_interviews_application (job_application_id),
    CONSTRAINT fk_interviews_application FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_interviews_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
