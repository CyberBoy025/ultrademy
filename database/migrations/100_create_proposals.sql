-- The quotation sent to a corporate client.
--
-- Money in minor units, per-seat and total both stored. The total is stored rather than
-- always derived because a proposal is a document that was sent: if the seat price
-- changes next quarter, what the client was quoted must not change with it.
CREATE TABLE IF NOT EXISTS proposals (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference        VARCHAR(30) NOT NULL,
    request_id       BIGINT UNSIGNED NULL,
    organisation_id  BIGINT UNSIGNED NOT NULL,
    programme_id     BIGINT UNSIGNED NULL,
    title            VARCHAR(200) NOT NULL,
    scope            TEXT NULL COMMENT 'what is being delivered',
    participants     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_amount      BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'per seat, minor units',
    discount_amount  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_amount     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    currency         CHAR(3) NOT NULL DEFAULT 'NGN',
    delivery_mode    ENUM('physical','online','hybrid') NOT NULL DEFAULT 'physical',
    centre_id        BIGINT UNSIGNED NULL,
    starts_on        DATE NULL,
    ends_on          DATE NULL,
    valid_until      DATE NULL,
    status           ENUM('draft','sent','accepted','declined','expired','withdrawn') NOT NULL DEFAULT 'draft',
    sent_at          TIMESTAMP NULL DEFAULT NULL,
    decided_at       TIMESTAMP NULL DEFAULT NULL,
    decision_note    VARCHAR(255) NULL,
    created_by       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_proposal_reference (reference),
    KEY ix_proposal_org (organisation_id, status),
    CONSTRAINT fk_proposal_request   FOREIGN KEY (request_id) REFERENCES training_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_proposal_org       FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    CONSTRAINT fk_proposal_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE SET NULL,
    CONSTRAINT fk_proposal_centre    FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL,
    CONSTRAINT fk_proposal_creator   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
