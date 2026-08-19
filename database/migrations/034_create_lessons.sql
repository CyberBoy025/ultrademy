CREATE TABLE IF NOT EXISTS lessons (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id         BIGINT UNSIGNED NOT NULL,
    title             VARCHAR(150) NOT NULL,
    content_type      ENUM('video','text','document','link') NOT NULL DEFAULT 'text',
    body              LONGTEXT NULL COMMENT 'lesson text for content_type=text',
    duration_minutes  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    sort_order        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_preview        TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'visible without an enrolment — a teaser',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_lessons_module (module_id, sort_order),
    CONSTRAINT fk_lessons_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
