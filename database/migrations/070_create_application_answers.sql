CREATE TABLE IF NOT EXISTS application_answers (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_application_id          BIGINT UNSIGNED NOT NULL,
    job_question_id             BIGINT UNSIGNED NOT NULL,
    answer_text                 TEXT NULL,
    answer_file_stored_name     VARCHAR(255) NULL,
    answer_file_original_name   VARCHAR(255) NULL,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application_answers (job_application_id, job_question_id),
    UNIQUE KEY uq_application_answers_file (answer_file_stored_name),
    CONSTRAINT fk_application_answers_application FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_answers_question FOREIGN KEY (job_question_id) REFERENCES job_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
