-- Donation campaigns — the cause a supporter is giving to (README §9b).
--
-- A campaign is public content, so it carries the same draft → published lifecycle as
-- programmes and blog posts (§78). Nothing is collectable until someone publishes it.
CREATE TABLE IF NOT EXISTS donation_campaigns (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug           VARCHAR(120) NOT NULL,
    title          VARCHAR(200) NOT NULL,
    summary        VARCHAR(500) NULL COMMENT 'one line, shown on cards',
    story          TEXT NULL COMMENT 'the full case for support',
    target_amount  BIGINT UNSIGNED NULL COMMENT 'minor units; NULL = no target, no progress bar',
    currency       CHAR(3) NOT NULL DEFAULT 'NGN',
    -- NULL means the general fund. A campaign earmarked for a centre attributes its
    -- income to that centre for §31 reporting; online/general giving must not be folded
    -- into a physical centre.
    centre_id      BIGINT UNSIGNED NULL,
    starts_on      DATE NULL,
    ends_on        DATE NULL,
    cover_path     VARCHAR(255) NULL,
    show_donor_wall TINYINT(1) NOT NULL DEFAULT 1,
    status         ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
    created_by     BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_campaign_slug (slug),
    KEY ix_campaign_status (status, ends_on),
    CONSTRAINT fk_campaign_centre  FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL,
    CONSTRAINT fk_campaign_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
