CREATE TABLE IF NOT EXISTS class_sessions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_group_id  BIGINT UNSIGNED NOT NULL,
    room_id         BIGINT UNSIGNED NULL COMMENT 'NULL = online session',
    lesson_id       BIGINT UNSIGNED NULL COMMENT 'FK added once the LMS lessons table exists (Phase 8)',
    topic           VARCHAR(200) NULL,
    starts_at       DATETIME NOT NULL,
    ends_at         DATETIME NOT NULL,
    mode            ENUM('physical','online') NOT NULL DEFAULT 'physical',
    status          ENUM('scheduled','held','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_class_sessions_group (class_group_id, starts_at),
    KEY ix_class_sessions_room (room_id),
    CONSTRAINT fk_class_sessions_group FOREIGN KEY (class_group_id) REFERENCES class_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_sessions_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
