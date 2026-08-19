-- An announcement is authored once and then fans out into one notification per
-- recipient. Keeping the announcement itself means "who was told what, and by whom"
-- survives even after individual notifications are read or pruned.
CREATE TABLE IF NOT EXISTS announcements (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(200) NOT NULL,
    body           TEXT NOT NULL,
    audience       ENUM('all','students','applicants','staff','centre','cohort') NOT NULL DEFAULT 'all',
    centre_id      BIGINT UNSIGNED NULL COMMENT 'when audience = centre',
    cohort_id      BIGINT UNSIGNED NULL COMMENT 'when audience = cohort',
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    published_by   BIGINT UNSIGNED NULL,
    published_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_announcements_published (published_at),
    CONSTRAINT fk_announcements_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL,
    CONSTRAINT fk_announcements_cohort FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE SET NULL,
    CONSTRAINT fk_announcements_publisher FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
