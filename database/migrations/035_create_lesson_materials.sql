CREATE TABLE IF NOT EXISTS lesson_materials (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id      BIGINT UNSIGNED NOT NULL,
    type           ENUM('video','document','link') NOT NULL DEFAULT 'document',
    title          VARCHAR(200) NOT NULL,
    url            VARCHAR(500) NULL COMMENT 'for type=link/video hosted elsewhere',
    stored_name    VARCHAR(255) NULL COMMENT 'random filename under storage/app/materials — uploads only',
    original_name  VARCHAR(255) NULL,
    mime_type      VARCHAR(100) NULL,
    size_bytes     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    is_downloadable TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'README §20: "download permitted materials"',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_material_stored_name (stored_name),
    KEY ix_materials_lesson (lesson_id),
    CONSTRAINT fk_materials_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
