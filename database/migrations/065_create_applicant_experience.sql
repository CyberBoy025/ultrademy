CREATE TABLE IF NOT EXISTS applicant_experience (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id               BIGINT UNSIGNED NOT NULL,
    organisation          VARCHAR(180) NOT NULL,
    job_title             VARCHAR(160) NOT NULL,
    employment_type       ENUM('full_time','part_time','contract','internship','volunteer','freelance') NULL,
    start_date            DATE NULL,
    end_date              DATE NULL,
    is_current            TINYINT(1) NOT NULL DEFAULT 0,
    responsibilities      TEXT NULL,
    reason_for_leaving    VARCHAR(255) NULL,
    sort_order            INT UNSIGNED NOT NULL DEFAULT 0,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_applicant_experience_user (user_id, sort_order),
    CONSTRAINT fk_applicant_experience_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
