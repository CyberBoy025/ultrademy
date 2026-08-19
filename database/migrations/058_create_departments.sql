-- Recruitment (docs/architecture/16-careers-portal.md). A department groups job postings
-- for filtering/display — deliberately separate from `job_categories` (brief §35 vs §6:
-- "Technology" is a department; "Full-Stack Developer" roles might span several).
CREATE TABLE IF NOT EXISTS departments (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    slug       VARCHAR(140) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_departments_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
