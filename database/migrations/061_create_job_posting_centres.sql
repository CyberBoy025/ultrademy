-- Recruitment. Reuses the existing `centres` table (README §12 — Gwagwalada Hub, Kubwa
-- Hub) rather than duplicating location data; a posting may span more than one centre.
CREATE TABLE IF NOT EXISTS job_posting_centres (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_posting_id BIGINT UNSIGNED NOT NULL,
    centre_id      BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_job_posting_centres (job_posting_id, centre_id),
    CONSTRAINT fk_job_posting_centres_posting FOREIGN KEY (job_posting_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_posting_centres_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
