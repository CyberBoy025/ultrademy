CREATE TABLE IF NOT EXISTS assignments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id           BIGINT UNSIGNED NOT NULL,
    title               VARCHAR(200) NOT NULL,
    instructions        TEXT NULL,
    due_at              DATETIME NULL,
    max_score           SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    allows_file         TINYINT(1) NOT NULL DEFAULT 1,
    allows_text         TINYINT(1) NOT NULL DEFAULT 1,
    allows_resubmission TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'README §23: "resubmission where enabled"',
    status              ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    created_by          BIGINT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_assignments_course (course_id, status),
    CONSTRAINT fk_assignments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
