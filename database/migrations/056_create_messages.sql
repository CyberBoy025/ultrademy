CREATE TABLE IF NOT EXISTS messages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_id       BIGINT UNSIGNED NULL COMMENT 'NULL = system message',
    body            TEXT NOT NULL,
    attachment_stored_name   VARCHAR(255) NULL,
    attachment_original_name VARCHAR(255) NULL,
    attachment_mime          VARCHAR(100) NULL,
    attachment_size          BIGINT UNSIGNED NOT NULL DEFAULT 0,
    edited_at       TIMESTAMP NULL DEFAULT NULL,
    -- 02-data-model.md §9: "Deleting a message is a soft delete with deleted_by, because
    -- §39 requires moderation actions to be auditable and a hard delete destroys the
    -- evidence."
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    deleted_by      BIGINT UNSIGNED NULL,
    deleted_reason  VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_message_attachment (attachment_stored_name),
    KEY ix_messages_conversation (conversation_id, created_at),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_messages_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
