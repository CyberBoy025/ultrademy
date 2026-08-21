-- A donation: the supporter's intent and identity. The MONEY lives in invoices and
-- payments, exactly as it does for enrolments and subscriptions.
--
-- 05-finance-payments.md §1 and the Phase 9b note: one ledger, not two. A parallel
-- money path would mean reconciliation misses donations, receipts number separately,
-- centre attribution breaks, and the accountant closes two systems each month.
--
-- `donor_user_id` is NOT NULL despite guests being welcome. `invoices.user_id` and
-- `payments.user_id` are both NOT NULL with foreign keys, so a donation without a user
-- could not be invoiced or receipted at all. Rather than make three financial tables
-- nullable, a guest donor gets a real `users` row with no usable password — which is
-- also what README §3 asks for: one identity record per person, gaining relationships
-- over time. A donor who registers later is the same person, not a duplicate.
CREATE TABLE IF NOT EXISTS donations (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference      VARCHAR(30) NOT NULL,
    public_token   CHAR(32) NOT NULL COMMENT 'unguessable handle for the guest status page',
    campaign_id    BIGINT UNSIGNED NULL COMMENT 'NULL = general fund',
    donor_user_id  BIGINT UNSIGNED NOT NULL,
    invoice_id     BIGINT UNSIGNED NULL COMMENT 'set once the invoice is raised',
    donor_name     VARCHAR(150) NOT NULL,
    donor_email    VARCHAR(255) NOT NULL,
    donor_phone    VARCHAR(32) NULL,
    amount         BIGINT UNSIGNED NOT NULL COMMENT 'minor units',
    currency       CHAR(3) NOT NULL DEFAULT 'NGN',
    is_anonymous   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'hide the name on the donor wall',
    message        VARCHAR(500) NULL COMMENT 'optional public note',
    centre_id      BIGINT UNSIGNED NULL COMMENT 'NULL = general/online (§31)',
    status         ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    completed_at   TIMESTAMP NULL DEFAULT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_donation_reference (reference),
    UNIQUE KEY uq_donation_token (public_token),
    KEY ix_donation_campaign (campaign_id, status),
    KEY ix_donation_donor (donor_user_id, status),
    KEY ix_donation_centre (centre_id, status),
    CONSTRAINT fk_donation_campaign FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(id) ON DELETE SET NULL,
    CONSTRAINT fk_donation_user     FOREIGN KEY (donor_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_donation_invoice  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    CONSTRAINT fk_donation_centre   FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
