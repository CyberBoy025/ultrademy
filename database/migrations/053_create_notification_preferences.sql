-- §2 "User preferences per notification type per channel, with a small set that cannot
-- be disabled: security, payment, and admission decisions."
--
-- Stored as opt-OUT rows: a missing row means "on". That way adding a new notification
-- type does not require backfilling a row for every user before it can be delivered.
CREATE TABLE IF NOT EXISTS notification_preferences (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    category   VARCHAR(30) NOT NULL,
    channel    ENUM('in_app','email','sms') NOT NULL,
    enabled    TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_preference (user_id, category, channel),
    CONSTRAINT fk_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
