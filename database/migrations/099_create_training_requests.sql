-- An enquiry: "we have 30 people who need this training".
--
-- `organisation_id` is nullable because a request can arrive from the public website
-- before anyone has created the organisation record. The typed organisation name is kept
-- alongside so nothing is lost, and linking is a deliberate act by whoever triages it.
CREATE TABLE IF NOT EXISTS training_requests (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference          VARCHAR(30) NOT NULL,
    organisation_id    BIGINT UNSIGNED NULL,
    organisation_name  VARCHAR(200) NOT NULL COMMENT 'as typed, before any record exists',
    contact_name       VARCHAR(150) NOT NULL,
    contact_email      VARCHAR(255) NOT NULL,
    contact_phone      VARCHAR(32) NULL,
    programme_id       BIGINT UNSIGNED NULL COMMENT 'NULL = bespoke, described in requirements',
    participants       SMALLINT UNSIGNED NULL,
    preferred_start    DATE NULL,
    delivery_mode      ENUM('physical','online','hybrid','unspecified') NOT NULL DEFAULT 'unspecified',
    centre_id          BIGINT UNSIGNED NULL COMMENT 'preferred hub, if physical',
    requirements       TEXT NULL,
    source             ENUM('public_form','staff','referral') NOT NULL DEFAULT 'staff',
    status             ENUM('new','reviewing','proposed','won','lost','withdrawn') NOT NULL DEFAULT 'new',
    lost_reason        VARCHAR(255) NULL,
    assigned_to        BIGINT UNSIGNED NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_request_reference (reference),
    KEY ix_request_status (status, created_at),
    KEY ix_request_org (organisation_id),
    CONSTRAINT fk_request_org       FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE SET NULL,
    CONSTRAINT fk_request_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE SET NULL,
    CONSTRAINT fk_request_centre    FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL,
    CONSTRAINT fk_request_assignee  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
