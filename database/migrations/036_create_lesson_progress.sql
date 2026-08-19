CREATE TABLE IF NOT EXISTS lesson_progress (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED NOT NULL,
    lesson_id     BIGINT UNSIGNED NOT NULL,
    enrolment_id  BIGINT UNSIGNED NULL COMMENT 'NULL for a standalone course consumed by subscription',
    progress_pct  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    completed_at  TIMESTAMP NULL DEFAULT NULL,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- One progress row per person per lesson, so marking complete twice is idempotent.
    UNIQUE KEY uq_progress_user_lesson (user_id, lesson_id),
    KEY ix_progress_enrolment (enrolment_id),
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_progress_enrolment FOREIGN KEY (enrolment_id) REFERENCES enrolments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
