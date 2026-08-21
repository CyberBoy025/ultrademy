-- People at a corporate client.
--
-- `user_id` is nullable: an HR manager who only ever appears on an email thread does not
-- need an account. It is populated when they need one — to receive an invoice, or to read
-- the corporate report.
CREATE TABLE IF NOT EXISTS organisation_contacts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organisation_id BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NULL,
    name            VARCHAR(150) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    phone           VARCHAR(32) NULL,
    job_title       VARCHAR(120) NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    is_billing      TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'invoices are addressed to this person',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_contact_org_email (organisation_id, email),
    KEY ix_contact_user (user_id),
    CONSTRAINT fk_contact_org  FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
