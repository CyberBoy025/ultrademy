CREATE TABLE IF NOT EXISTS applicant_education (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     BIGINT UNSIGNED NOT NULL,
    institution                 VARCHAR(180) NOT NULL,
    qualification               VARCHAR(160) NOT NULL,
    field_of_study               VARCHAR(160) NULL,
    start_year                  SMALLINT UNSIGNED NULL,
    end_year                    SMALLINT UNSIGNED NULL,
    certificate_stored_name     VARCHAR(255) NULL,
    certificate_original_name   VARCHAR(255) NULL,
    sort_order                  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_applicant_education_user (user_id, sort_order),
    UNIQUE KEY uq_applicant_education_certificate (certificate_stored_name),
    CONSTRAINT fk_applicant_education_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
