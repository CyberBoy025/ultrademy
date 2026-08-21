-- Answer options for choice questions. `is_correct` must never reach the browser before
-- an attempt is graded — Assessment::questionsForTaking() exists solely to enforce that.
CREATE TABLE IF NOT EXISTS assessment_options (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    label       VARCHAR(500) NOT NULL,
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_options_question (question_id, sort_order),
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
