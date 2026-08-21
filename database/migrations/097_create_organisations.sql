-- Corporate clients (README §46) — banks, government agencies, parastatals, companies,
-- institutions.
--
-- An organisation is NOT a user. People at the organisation are users; the organisation
-- itself is a counterparty. Conflating the two would mean inventing a fake account to
-- represent a company, which then appears in student lists and notification audiences.
CREATE TABLE IF NOT EXISTS organisations (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(200) NOT NULL,
    slug           VARCHAR(120) NOT NULL,
    type           ENUM('company','bank','government','parastatal','ngo','institution','other') NOT NULL DEFAULT 'company',
    registration_no VARCHAR(60) NULL COMMENT 'RC number or equivalent',
    industry       VARCHAR(120) NULL,
    address_line   VARCHAR(255) NULL,
    city           VARCHAR(80) NULL,
    state          VARCHAR(80) NULL,
    website        VARCHAR(255) NULL,
    notes          TEXT NULL,
    status         ENUM('prospect','active','dormant','blocked') NOT NULL DEFAULT 'prospect',
    created_by     BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_org_slug (slug),
    KEY ix_org_status (status, name),
    CONSTRAINT fk_org_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
