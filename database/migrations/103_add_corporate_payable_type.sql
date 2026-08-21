-- Corporate contracts join the invoice spine — the fourth payable kind, and the fourth
-- time this enum has absorbed a new business model without a new money path
-- (02-data-model.md §7).
ALTER TABLE invoices
    MODIFY COLUMN payable_type
    ENUM('enrolment','subscription','application_fee','donation','corporate_contract','other')
    NOT NULL DEFAULT 'other';

INSERT IGNORE INTO settings (`key`, `value`, `group`, `is_public`) VALUES
    ('corporate_enabled', '"0"', 'corporate', 1),
    ('corporate_proposal_validity_days', '"30"', 'corporate', 0);
