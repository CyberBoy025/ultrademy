-- One row per question per attempt.
--
-- `selected_options` is a JSON array of option ids rather than a join table: an answer is
-- always read and written whole, never queried by individual option, so a join table
-- would add a table and a join for no query it enables. MariaDB stores JSON as LONGTEXT
-- with a validity CHECK, which is exactly the guarantee needed here.
CREATE TABLE IF NOT EXISTS assessment_answers (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id       BIGINT UNSIGNED NOT NULL,
    question_id      BIGINT UNSIGNED NOT NULL,
    selected_options JSON NULL COMMENT 'array of assessment_options.id, for choice questions',
    response_text    TEXT NULL COMMENT 'short_text and essay',
    awarded_points   SMALLINT UNSIGNED NULL,
    is_correct       TINYINT(1) NULL COMMENT 'NULL until graded; always NULL for essay until a human decides',
    feedback         TEXT NULL,
    graded_by        BIGINT UNSIGNED NULL,
    graded_at        TIMESTAMP NULL DEFAULT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_answer (attempt_id, question_id),
    KEY ix_answers_question (question_id),
    CONSTRAINT fk_answers_attempt  FOREIGN KEY (attempt_id) REFERENCES assessment_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_answers_grader   FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
