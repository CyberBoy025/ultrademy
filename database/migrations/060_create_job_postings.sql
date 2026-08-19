-- Recruitment (docs/architecture/16-careers-portal.md §5). `location_type` covers brief
-- §6's "architecture must allow future locations": a posting is either tied to one or more
-- real centres (see job_posting_centres) or carries a free-text label for remote/head
-- office/other, so a new physical location never requires a schema change.
CREATE TABLE IF NOT EXISTS job_postings (
    id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title                    VARCHAR(160) NOT NULL,
    slug                     VARCHAR(180) NOT NULL,
    department_id            BIGINT UNSIGNED NULL,
    category_id              BIGINT UNSIGNED NULL,
    employment_type          ENUM('full_time','part_time','contract','internship','temporary','volunteer') NOT NULL DEFAULT 'full_time',
    work_mode                ENUM('onsite','hybrid','remote') NOT NULL DEFAULT 'onsite',
    location_type            ENUM('centre','remote','multiple_centres','head_office','other') NOT NULL DEFAULT 'centre',
    location_label           VARCHAR(160) NULL COMMENT 'shown when location_type is not resolvable to a single centre row',
    summary                  TEXT NULL,
    responsibilities         TEXT NULL,
    requirements              TEXT NULL,
    qualifications            TEXT NULL,
    skills                   TEXT NULL,
    experience_requirements  TEXT NULL,
    benefits                 TEXT NULL,
    application_deadline     DATETIME NULL,
    status                   ENUM('draft','published','unpublished','closed') NOT NULL DEFAULT 'draft',
    published_at             TIMESTAMP NULL DEFAULT NULL,
    closed_at                TIMESTAMP NULL DEFAULT NULL,
    created_by               BIGINT UNSIGNED NULL,
    created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_postings_slug (slug),
    KEY ix_job_postings_status (status, application_deadline),
    KEY ix_job_postings_department (department_id),
    KEY ix_job_postings_category (category_id),
    CONSTRAINT fk_job_postings_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_job_postings_category FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_job_postings_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
