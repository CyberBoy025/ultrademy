CREATE TABLE IF NOT EXISTS cohorts (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    programme_id  BIGINT UNSIGNED NOT NULL,
    centre_id     BIGINT UNSIGNED NULL COMMENT 'NULL = online cohort',
    code          VARCHAR(30) NOT NULL,
    name          VARCHAR(150) NOT NULL,
    starts_on     DATE NULL,
    ends_on       DATE NULL,
    capacity      SMALLINT UNSIGNED NULL,
    status        ENUM('planned','open','running','completed','cancelled') NOT NULL DEFAULT 'planned',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cohorts_code (code),
    KEY ix_cohorts_programme (programme_id),
    KEY ix_cohorts_centre (centre_id),
    CONSTRAINT fk_cohorts_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    CONSTRAINT fk_cohorts_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
