-- Affiliate permissions and settings.
--
-- Approving an affiliate (admin work) is separate from approving their commissions
-- (finance work) and from paying them (finance work). §8 of 05-finance-payments.md: the
-- person who creates a financial obligation should not also be the one who settles it.
INSERT IGNORE INTO permissions (code, module) VALUES
    ('affiliate.application.review', 'affiliate'),
    ('affiliate.application.approve', 'affiliate'),
    ('affiliate.referral.view_any', 'affiliate'),
    ('affiliate.commission.approve', 'affiliate'),
    ('affiliate.payout.process', 'affiliate');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'super_admin' AND p.module = 'affiliate';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'administrator'
  AND p.code IN ('affiliate.application.review','affiliate.application.approve','affiliate.referral.view_any');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'management'
  AND p.code IN ('affiliate.referral.view_any','affiliate.commission.approve');

-- The accountant pays what management has approved; they do not approve it themselves.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'accountant' AND p.code IN ('affiliate.referral.view_any','affiliate.payout.process');

INSERT IGNORE INTO settings (`key`, `value`, `group`, `is_public`) VALUES
    ('affiliate_enabled', '"0"', 'affiliate', 1),
    ('affiliate_default_rate_bps', '"500"', 'affiliate', 0),
    ('affiliate_cookie_days', '"30"', 'affiliate', 0),
    ('affiliate_min_payout', '"500000"', 'affiliate', 0);
