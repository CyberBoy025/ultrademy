-- Assessments: quizzes and examinations (README §18-§20).
--
-- Deliberately separate from `assignments`. An assignment is submitted work a human
-- reads and grades; an assessment is a set of questions answered under rules — attempt
-- limits, a clock, a pass mark — and is mostly machine-graded. Folding the two together
-- would mean either assignments carry meaningless timing columns or assessments lose them.
CREATE TABLE IF NOT EXISTS assessments (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id         BIGINT UNSIGNED NOT NULL,
    module_id         BIGINT UNSIGNED NULL COMMENT 'optional: end-of-module quiz',
    title             VARCHAR(200) NOT NULL,
    instructions      TEXT NULL,
    type              ENUM('quiz','exam') NOT NULL DEFAULT 'quiz',
    opens_at          DATETIME NULL,
    closes_at         DATETIME NULL,
    duration_minutes  SMALLINT UNSIGNED NULL COMMENT 'NULL = untimed',
    max_attempts      TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '0 = unlimited',
    pass_mark         TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'percent',
    shuffle_questions TINYINT(1) NOT NULL DEFAULT 0,
    show_results      ENUM('immediately','after_close','never') NOT NULL DEFAULT 'immediately',
    status            ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    created_by        BIGINT UNSIGNED NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_assessments_course (course_id, status),
    KEY ix_assessments_window (status, opens_at, closes_at),
    CONSTRAINT fk_assessments_course  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_assessments_module  FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_assessments_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
