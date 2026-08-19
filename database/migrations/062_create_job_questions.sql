-- Recruitment (brief §16 — recruiters attach questions to a specific posting, not
-- globally). `options` holds a JSON array for the multiple_choice type only.
CREATE TABLE IF NOT EXISTS job_questions (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_posting_id BIGINT UNSIGNED NOT NULL,
    label          VARCHAR(255) NOT NULL,
    type           ENUM('short_text','long_text','yes_no','multiple_choice','date','number','file') NOT NULL DEFAULT 'short_text',
    options        TEXT NULL COMMENT 'JSON array, multiple_choice only',
    is_required    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order     INT UNSIGNED NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_job_questions_posting (job_posting_id, sort_order),
    CONSTRAINT fk_job_questions_posting FOREIGN KEY (job_posting_id) REFERENCES job_postings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
