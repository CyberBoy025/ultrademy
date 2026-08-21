-- Permissions for donations.
--
-- `donation.campaign.manage` is content work (an administrator writes the appeal).
-- `donation.view_any` is financial (an accountant reconciles the income). They are
-- separate because the person who writes the fundraising copy has no reason to see
-- every supporter's name, email and amount.
INSERT IGNORE INTO permissions (code, module) VALUES
    ('donation.campaign.manage', 'donations'),
    ('donation.view_any',        'donations'),
    ('donation.export',          'donations');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'super_admin'
  AND p.code IN ('donation.campaign.manage','donation.view_any','donation.export');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'administrator' AND p.code = 'donation.campaign.manage';

-- Finance sees the money and may export it for reconciliation; it does not write appeals.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'accountant' AND p.code IN ('donation.view_any','donation.export');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'management' AND p.code = 'donation.view_any';
