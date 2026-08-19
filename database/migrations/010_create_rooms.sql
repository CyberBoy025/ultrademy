CREATE TABLE IF NOT EXISTS rooms (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    centre_id   BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    type        ENUM('classroom','computer_lab','office','hall') NOT NULL DEFAULT 'classroom',
    capacity    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status      ENUM('available','maintenance','retired') NOT NULL DEFAULT 'available',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_rooms_centre (centre_id),
    CONSTRAINT fk_rooms_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
