-- Corporate training permissions.
--
-- Selling (requests, proposals) is separated from committing the company (contracts).
-- A salesperson may quote; signing is a management act. Same shape as every other
-- approve/execute split in this system.
INSERT IGNORE INTO permissions (code, module) VALUES
    ('corporate.organisation.manage', 'corporate'),
    ('corporate.request.manage',      'corporate'),
    ('corporate.proposal.manage',     'corporate'),
    ('corporate.contract.approve',    'corporate'),
    ('corporate.participant.manage',  'corporate'),
    ('corporate.report.view',         'corporate');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'super_admin' AND p.module = 'corporate';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'management' AND p.module = 'corporate';

-- Administrators run the pipeline but do not sign contracts.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'administrator'
  AND p.code IN ('corporate.organisation.manage','corporate.request.manage',
                 'corporate.proposal.manage','corporate.participant.manage','corporate.report.view');

-- A centre manager delivering a contract needs the participant list and the report,
-- scoped to their centre by the usual resolver.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'centre_manager' AND p.code IN ('corporate.participant.manage','corporate.report.view');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'accountant' AND p.code = 'corporate.report.view';
