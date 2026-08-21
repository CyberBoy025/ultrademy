-- Questions belonging to an assessment.
--
-- `points` is per question rather than a flat mark per assessment, so an exam can weight
-- a five-mark essay against a one-mark true/false. The assessment's maximum is the SUM
-- of its questions' points — never stored on the assessment itself, because a cached
-- total silently goes stale the moment a question is added or deleted.
CREATE TABLE IF NOT EXISTS assessment_questions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id   BIGINT UNSIGNED NOT NULL,
    type            ENUM('single_choice','multi_choice','true_false','short_text','essay') NOT NULL DEFAULT 'single_choice',
    prompt          TEXT NOT NULL,
    points          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    -- Only for short_text. Compared case-insensitively after trimming; pipe-separated
    -- for accepted alternatives, e.g. "HTTP|Hypertext Transfer Protocol".
    expected_answer VARCHAR(500) NULL,
    explanation     TEXT NULL COMMENT 'shown in review when results are visible',
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_questions_assessment (assessment_id, sort_order),
    CONSTRAINT fk_questions_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
