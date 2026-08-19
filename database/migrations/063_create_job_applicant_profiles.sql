-- Recruitment. Mirrors `user_profiles` (migration 002) but holds résumé-shaped data that
-- has no home on the shared profile. One row per user, by design — see 02-data-model.md's
-- "no applicants table" precedent, extended here: there is no separate applicant identity,
-- only a résumé attached to the existing user (docs/architecture/16-careers-portal.md §5).
CREATE TABLE IF NOT EXISTS job_applicant_profiles (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id               BIGINT UNSIGNED NOT NULL,
    professional_summary  TEXT NULL,
    current_occupation    VARCHAR(160) NULL,
    years_experience      TINYINT UNSIGNED NULL,
    career_interests      VARCHAR(255) NULL,
    resume_original_name  VARCHAR(255) NULL,
    resume_stored_name    VARCHAR(255) NULL COMMENT 'random filename under storage/app/recruitment — never the users own name',
    resume_mime_type      VARCHAR(100) NULL,
    resume_size_bytes     BIGINT UNSIGNED NULL,
    completion_pct        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_applicant_profiles_user (user_id),
    UNIQUE KEY uq_job_applicant_profiles_resume (resume_stored_name),
    CONSTRAINT fk_job_applicant_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
