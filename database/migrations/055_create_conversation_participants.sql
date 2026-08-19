CREATE TABLE IF NOT EXISTS conversation_participants (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    role            ENUM('member','moderator') NOT NULL DEFAULT 'member',
    joined_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at         TIMESTAMP NULL DEFAULT NULL COMMENT 'kept rather than deleted so history stays attributable',
    muted_until     TIMESTAMP NULL DEFAULT NULL,
    last_read_at    TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_participant (conversation_id, user_id),
    KEY ix_participant_user (user_id, left_at),
    CONSTRAINT fk_participants_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
