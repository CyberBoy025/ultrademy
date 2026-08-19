CREATE TABLE IF NOT EXISTS application_documents (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  BIGINT UNSIGNED NOT NULL,
    type            ENUM('id_card','certificate','passport_photo','other') NOT NULL DEFAULT 'other',
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) NOT NULL COMMENT 'random filename under storage/app/documents — never the users own name',
    mime_type       VARCHAR(100) NOT NULL,
    size_bytes      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status          ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    reviewer_note   VARCHAR(255) NULL,
    uploaded_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_document_stored_name (stored_name),
    KEY ix_documents_application (application_id),
    CONSTRAINT fk_documents_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
