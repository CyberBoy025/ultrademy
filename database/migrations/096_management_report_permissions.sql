-- Management reporting permission.
--
-- Distinct from `finance.report.view`, which is about money. This one covers the
-- operational picture — students, admissions, attendance, teaching — and is scopable, so
-- a centre manager granted it sees their own centre and nothing else. The scope is
-- applied by Auth::scopeCentres() in exactly the same way as every other listing;
-- reporting does not get its own access model, because that is how data leaks.
INSERT IGNORE INTO permissions (code, module) VALUES
    ('management.report.view', 'reporting');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code IN ('super_admin', 'management') AND p.code = 'management.report.view';

-- Granted globally to administrators (they already see across centres) and to
-- accountants, whose financial reports are more useful beside the operational ones.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code IN ('administrator', 'accountant') AND p.code = 'management.report.view';

-- Centre managers get it too, but their existing role assignment is already centre-scoped,
-- so scopeCentres() narrows every query to the centre(s) they actually manage
-- (Decision 8: they do not see online-only activity either).
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'centre_manager' AND p.code = 'management.report.view';
