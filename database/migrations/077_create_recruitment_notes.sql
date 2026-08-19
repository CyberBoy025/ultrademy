-- INTERNAL ONLY (brief §31) — never queried by any applicant-facing controller.
CREATE TABLE IF NOT EXISTS recruitment_notes (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_application_id   BIGINT UNSIGNED NOT NULL,
    author_id            BIGINT UNSIGNED NULL,
    note                 TEXT NOT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_recruitment_notes_application (job_application_id, created_at),
    CONSTRAINT fk_recruitment_notes_application FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_recruitment_notes_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
