CREATE TABLE IF NOT EXISTS programme_centres (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    programme_id  BIGINT UNSIGNED NOT NULL,
    centre_id     BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_programme_centre (programme_id, centre_id),
    CONSTRAINT fk_programme_centres_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    CONSTRAINT fk_programme_centres_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
