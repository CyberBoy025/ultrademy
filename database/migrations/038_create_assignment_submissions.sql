CREATE TABLE IF NOT EXISTS assignment_submissions (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id  BIGINT UNSIGNED NOT NULL,
    user_id        BIGINT UNSIGNED NOT NULL,
    enrolment_id   BIGINT UNSIGNED NULL,
    body           TEXT NULL COMMENT 'text submission',
    stored_name    VARCHAR(255) NULL COMMENT 'random filename under storage/app/submissions',
    original_name  VARCHAR(255) NULL,
    mime_type      VARCHAR(100) NULL,
    size_bytes     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status         ENUM('submitted','graded','returned') NOT NULL DEFAULT 'submitted',
    score          SMALLINT UNSIGNED NULL,
    feedback       TEXT NULL,
    graded_by      BIGINT UNSIGNED NULL,
    graded_at      TIMESTAMP NULL DEFAULT NULL,
    submitted_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- One submission row per student per assignment; resubmission overwrites it rather
    -- than piling up duplicates that graders would have to disambiguate.
    UNIQUE KEY uq_submission_assignment_user (assignment_id, user_id),
    UNIQUE KEY uq_submission_stored_name (stored_name),
    KEY ix_submissions_grading (assignment_id, status),
    CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_enrolment FOREIGN KEY (enrolment_id) REFERENCES enrolments(id) ON DELETE SET NULL,
    CONSTRAINT fk_submissions_grader FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
