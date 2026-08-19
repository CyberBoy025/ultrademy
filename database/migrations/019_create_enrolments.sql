CREATE TABLE IF NOT EXISTS enrolments (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_no    VARCHAR(30) NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    programme_id  BIGINT UNSIGNED NOT NULL,
    cohort_id     BIGINT UNSIGNED NOT NULL,
    centre_id     BIGINT UNSIGNED NULL COMMENT 'denormalised from cohort at enrolment time — see data-model.md §4',
    status        ENUM('pending_payment','active','suspended','withdrawn','completed') NOT NULL DEFAULT 'active',
    enrolled_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at  TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_enrolments_student_no (student_no),
    KEY ix_enrolments_user (user_id, status),
    KEY ix_enrolments_centre (centre_id, status),
    KEY ix_enrolments_cohort (cohort_id),
    CONSTRAINT fk_enrolments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrolments_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrolments_cohort FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrolments_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
