-- INTERNAL ONLY (brief §23) — never queried by any applicant-facing controller.
CREATE TABLE IF NOT EXISTS interview_feedback (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    interview_id      BIGINT UNSIGNED NOT NULL,
    panelist_user_id  BIGINT UNSIGNED NOT NULL,
    score             TINYINT UNSIGNED NULL,
    evaluation        TEXT NULL,
    strengths         TEXT NULL,
    concerns          TEXT NULL,
    recommendation    ENUM('strong_yes','yes','no','strong_no') NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_interview_feedback (interview_id, panelist_user_id),
    CONSTRAINT fk_interview_feedback_interview FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
    CONSTRAINT fk_interview_feedback_panelist FOREIGN KEY (panelist_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
