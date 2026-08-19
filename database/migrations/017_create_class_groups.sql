CREATE TABLE IF NOT EXISTS class_groups (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cohort_id           BIGINT UNSIGNED NOT NULL,
    instructor_user_id  BIGINT UNSIGNED NULL,
    name                VARCHAR(100) NOT NULL,
    capacity            SMALLINT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_class_groups_cohort (cohort_id),
    KEY ix_class_groups_instructor (instructor_user_id),
    CONSTRAINT fk_class_groups_cohort FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_groups_instructor FOREIGN KEY (instructor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
