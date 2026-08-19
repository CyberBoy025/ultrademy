-- Recruitment. Named `job_applications` (not `applications`) and grants a `job_applicant`
-- role (not `applicant`) deliberately — this codebase already has an `applications` table
-- and an auto-granted `applicant` role for programme admissions
-- (docs/architecture/16-careers-portal.md §1, §5, §8). Colliding names would corrupt that
-- existing role/table's meaning.
CREATE TABLE IF NOT EXISTS job_applications (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference                 VARCHAR(30) NOT NULL,
    job_posting_id            BIGINT UNSIGNED NOT NULL,
    user_id                   BIGINT UNSIGNED NOT NULL,
    status                    ENUM('draft','submitted','received','under_review','shortlisted','interview','assessment','final_review','selected','rejected','withdrawn','closed') NOT NULL DEFAULT 'draft',
    cover_letter              TEXT NULL,
    declaration_accepted_at   TIMESTAMP NULL DEFAULT NULL,
    submitted_at              TIMESTAMP NULL DEFAULT NULL,
    reviewed_by               BIGINT UNSIGNED NULL,
    reviewed_at               TIMESTAMP NULL DEFAULT NULL,
    decision_note             TEXT NULL,
    created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_applications_reference (reference),
    UNIQUE KEY uq_job_applications_posting_user (job_posting_id, user_id),
    KEY ix_job_applications_user (user_id, status),
    KEY ix_job_applications_posting (job_posting_id, status),
    CONSTRAINT fk_job_applications_posting FOREIGN KEY (job_posting_id) REFERENCES job_postings(id) ON DELETE RESTRICT,
    CONSTRAINT fk_job_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_applications_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
