CREATE TABLE IF NOT EXISTS applicant_certifications (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                BIGINT UNSIGNED NOT NULL,
    name                   VARCHAR(180) NOT NULL,
    issuing_organisation   VARCHAR(180) NULL,
    issued_on              DATE NULL,
    expires_on             DATE NULL,
    stored_name            VARCHAR(255) NULL,
    original_name          VARCHAR(255) NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_applicant_certifications_user (user_id),
    UNIQUE KEY uq_applicant_certifications_stored (stored_name),
    CONSTRAINT fk_applicant_certifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
