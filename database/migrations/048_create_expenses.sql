CREATE TABLE IF NOT EXISTS expenses (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    centre_id     BIGINT UNSIGNED NULL COMMENT 'NULL = head office / global',
    category      VARCHAR(60) NOT NULL,
    amount        BIGINT UNSIGNED NOT NULL COMMENT 'minor units',
    currency      CHAR(3) NOT NULL DEFAULT 'NGN',
    description   VARCHAR(500) NULL,
    incurred_on   DATE NOT NULL,
    recorded_by   BIGINT UNSIGNED NULL,
    approved_by   BIGINT UNSIGNED NULL,
    status        ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'submitted',
    decided_at    TIMESTAMP NULL DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_expenses_centre (centre_id, status, incurred_on),
    CONSTRAINT fk_expenses_centre FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
