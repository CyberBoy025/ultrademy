-- Named distinctly from the existing `application_documents` (programme admissions ID
-- docs) — see 069's header note. Same storage discipline as ApplicationDocument /
-- Upload.php: outside the web root, random filenames, streamed only after an
-- authorisation check.
CREATE TABLE IF NOT EXISTS job_application_documents (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_application_id   BIGINT UNSIGNED NOT NULL,
    type                 ENUM('cv','cover_letter','certificate','credential','portfolio','identification','other') NOT NULL DEFAULT 'other',
    original_name        VARCHAR(255) NOT NULL,
    stored_name          VARCHAR(255) NOT NULL,
    mime_type            VARCHAR(100) NOT NULL,
    size_bytes           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_application_documents_stored (stored_name),
    KEY ix_job_application_documents_application (job_application_id),
    CONSTRAINT fk_job_application_documents_application FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
