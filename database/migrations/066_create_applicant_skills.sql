CREATE TABLE IF NOT EXISTS applicant_skills (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    skill_name   VARCHAR(120) NOT NULL,
    skill_type   ENUM('technical','professional','software','language') NOT NULL DEFAULT 'technical',
    proficiency  ENUM('beginner','intermediate','advanced','expert') NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_applicant_skills_user (user_id),
    CONSTRAINT fk_applicant_skills_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
