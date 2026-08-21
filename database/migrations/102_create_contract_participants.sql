-- The employees a corporate client nominates.
--
-- `user_id` is nullable until the person accepts their invitation, because the employer
-- supplies a name and an email — that is not consent to create an account, and it is not
-- proof the address belongs to who they say. The account appears when the person clicks
-- the link in their own inbox.
--
-- UNIQUE (contract_id, email) stops the same person being nominated twice and burning two
-- of the seats the client paid for.
CREATE TABLE IF NOT EXISTS contract_participants (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id   BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NULL,
    enrolment_id  BIGINT UNSIGNED NULL,
    name          VARCHAR(150) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    phone         VARCHAR(32) NULL,
    job_title     VARCHAR(120) NULL,
    invite_token  CHAR(32) NULL,
    invited_at    TIMESTAMP NULL DEFAULT NULL,
    accepted_at   TIMESTAMP NULL DEFAULT NULL,
    status        ENUM('nominated','invited','active','completed','withdrawn') NOT NULL DEFAULT 'nominated',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_participant_contract_email (contract_id, email),
    UNIQUE KEY uq_participant_token (invite_token),
    KEY ix_participant_status (contract_id, status),
    CONSTRAINT fk_participant_contract  FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    CONSTRAINT fk_participant_user      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_participant_enrolment FOREIGN KEY (enrolment_id) REFERENCES enrolments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
