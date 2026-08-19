CREATE TABLE IF NOT EXISTS conversations (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type         ENUM('direct','group','programme_cohort') NOT NULL DEFAULT 'direct',
    title        VARCHAR(150) NULL COMMENT 'NULL for direct — the title is the other person',
    cohort_id    BIGINT UNSIGNED NULL COMMENT 'set for an auto-created programme_cohort group',
    -- A direct conversation is identified by its two participants. Storing the pair as a
    -- sorted key here is what stops "message this person" creating a second thread every
    -- time it is clicked.
    direct_key   VARCHAR(40) NULL,
    created_by   BIGINT UNSIGNED NULL,
    is_moderated TINYINT(1) NOT NULL DEFAULT 0,
    is_archived  TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversation_direct (direct_key),
    UNIQUE KEY uq_conversation_cohort (cohort_id),
    CONSTRAINT fk_conversations_cohort FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE CASCADE,
    CONSTRAINT fk_conversations_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
