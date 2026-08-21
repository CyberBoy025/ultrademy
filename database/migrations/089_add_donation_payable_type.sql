-- Teach the invoice spine about donations.
--
-- This is the whole reason `payable_type` was made an enum over a polymorphic pair
-- rather than a nullable FK per kind (02-data-model.md §7): a new payable kind is one
-- ALTER, and every downstream behaviour — verification, receipts, refunds,
-- reconciliation, centre reporting — keeps working untouched.
ALTER TABLE invoices
    MODIFY COLUMN payable_type
    ENUM('enrolment','subscription','application_fee','donation','other')
    NOT NULL DEFAULT 'other';

-- Bank details shown to a supporter choosing manual transfer already exist as settings;
-- these two are donation-specific copy so the wording is editable without a deploy.
INSERT IGNORE INTO settings (`key`, `value`, `group`, `is_public`) VALUES
    ('donations_enabled', '"0"', 'donations', 1),
    ('donations_intro', '"Your gift funds training for people who could not otherwise afford it."', 'donations', 1);
