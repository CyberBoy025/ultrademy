CREATE TABLE IF NOT EXISTS notifications (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    type         VARCHAR(60) NOT NULL COMMENT 'application.approved, payment.verified, …',
    category     VARCHAR(30) NOT NULL DEFAULT 'general' COMMENT 'security|payment|admission|learning|general — drives which preferences may switch it off',
    title        VARCHAR(200) NOT NULL,
    body         VARCHAR(500) NULL,
    url          VARCHAR(255) NULL COMMENT 'where clicking it should go',
    data         JSON NULL,
    channel      ENUM('in_app','email','sms') NOT NULL DEFAULT 'in_app',
    -- 06-api-notifications.md §2: "More than 5 notifications of one type within an hour
    -- collapses into a single digest — otherwise a bulk attendance mark sends 200 emails."
    digest_count INT UNSIGNED NOT NULL DEFAULT 1,
    read_at      TIMESTAMP NULL DEFAULT NULL,
    sent_at      TIMESTAMP NULL DEFAULT NULL COMMENT 'in_app is sent on creation; email/sms wait for a transport',
    failed_at    TIMESTAMP NULL DEFAULT NULL,
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error   VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_notifications_inbox (user_id, channel, read_at, created_at),
    KEY ix_notifications_digest (user_id, type, channel, created_at),
    KEY ix_notifications_queue (channel, sent_at, failed_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
