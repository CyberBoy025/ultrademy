-- Token-substitution only ({{applicant_name}} etc, rendered by EmailTemplate.php) — never
-- an executable template language (brief §25's explicit safety requirement).
CREATE TABLE IF NOT EXISTS recruitment_email_templates (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(60) NOT NULL,
    name        VARCHAR(160) NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    body        TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recruitment_email_templates_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
