CREATE TABLE IF NOT EXISTS equipment (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    centre_id   BIGINT UNSIGNED NOT NULL,
    room_id     BIGINT UNSIGNED NULL,
    asset_tag   VARCHAR(50) NOT NULL,
    name        VARCHAR(150) NOT NULL,
    status      ENUM('in_service','repair','retired') NOT NULL DEFAULT 'in_service',
    acquired_on DATE NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_equipment_tag (asset_tag),
    KEY ix_equipment_centre (centre_id),
    CONSTRAINT fk_equipment_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE CASCADE,
    CONSTRAINT fk_equipment_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
