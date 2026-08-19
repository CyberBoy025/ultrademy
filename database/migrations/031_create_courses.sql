CREATE TABLE IF NOT EXISTS courses (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title              VARCHAR(150) NOT NULL,
    slug               VARCHAR(150) NOT NULL,
    description        TEXT NULL,
    objectives         TEXT NULL,
    prerequisites      TEXT NULL,
    estimated_minutes  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'derived from lessons, cached here for listings',
    standalone         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'sellable/consumable outside a programme (04-subscriptions §3)',
    status             ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    created_by         BIGINT UNSIGNED NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_courses_slug (slug),
    CONSTRAINT fk_courses_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
