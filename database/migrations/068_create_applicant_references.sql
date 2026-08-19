CREATE TABLE IF NOT EXISTS applicant_references (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED NOT NULL,
    name          VARCHAR(160) NOT NULL,
    relationship  VARCHAR(120) NULL,
    organisation  VARCHAR(160) NULL,
    email         VARCHAR(190) NULL,
    phone         VARCHAR(40) NULL,
    sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_applicant_references_user (user_id, sort_order),
    CONSTRAINT fk_applicant_references_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
