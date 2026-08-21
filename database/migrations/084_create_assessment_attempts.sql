-- One row per sitting. `attempt_no` is per (assessment, user), so max_attempts is
-- enforceable with a COUNT and the unique key makes a double-submitted form harmless.
--
-- max_points is snapshotted at submission. If an instructor edits the paper afterwards,
-- an already-graded attempt must keep the mark it was actually awarded out of the total
-- that actually applied — recomputing from the live questions would silently rewrite
-- historical results.
CREATE TABLE IF NOT EXISTS assessment_attempts (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id      BIGINT UNSIGNED NOT NULL,
    user_id            BIGINT UNSIGNED NOT NULL,
    enrolment_id       BIGINT UNSIGNED NULL,
    attempt_no         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status             ENUM('in_progress','submitted','graded','expired') NOT NULL DEFAULT 'in_progress',
    started_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at       TIMESTAMP NULL DEFAULT NULL,
    graded_at          TIMESTAMP NULL DEFAULT NULL,
    graded_by          BIGINT UNSIGNED NULL COMMENT 'NULL when fully auto-graded',
    score_points       SMALLINT UNSIGNED NULL,
    max_points         SMALLINT UNSIGNED NULL,
    score_percent      DECIMAL(5,2) NULL,
    passed             TINYINT(1) NULL,
    needs_manual_grade TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'contains essay answers',
    time_spent_seconds INT UNSIGNED NULL,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attempt (assessment_id, user_id, attempt_no),
    KEY ix_attempts_grading (assessment_id, status, needs_manual_grade),
    KEY ix_attempts_user (user_id, status),
    CONSTRAINT fk_attempts_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempts_user       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_attempts_enrolment  FOREIGN KEY (enrolment_id) REFERENCES enrolments(id) ON DELETE SET NULL,
    CONSTRAINT fk_attempts_grader     FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
