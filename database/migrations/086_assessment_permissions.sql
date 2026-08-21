-- Permissions for the assessments module, and their role grants.
--
-- Written as a migration rather than added to seed.php because seed.php is for demo
-- content: an existing installation must gain these permissions without being re-seeded.
-- Every statement is INSERT IGNORE, so re-running is harmless.
--
-- `education.assessment.grade` is separate from `education.assignment.grade` on purpose.
-- They are different acts — one marks free-form submitted work, the other adjudicates
-- essay answers inside a timed paper — and an organisation may well want a senior
-- examiner for the second. Both are granted to instructors by default, so nothing
-- changes operationally unless someone chooses to split them.
INSERT IGNORE INTO permissions (code, module) VALUES
    ('education.assessment.manage', 'education'),
    ('education.assessment.grade',  'education'),
    ('education.assessment.results','education');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'super_admin'
  AND p.code IN ('education.assessment.manage','education.assessment.grade','education.assessment.results');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'administrator'
  AND p.code IN ('education.assessment.manage','education.assessment.results');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'instructor'
  AND p.code IN ('education.assessment.manage','education.assessment.grade','education.assessment.results');

-- Management reads results across centres but does not author or mark papers.
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'management' AND p.code = 'education.assessment.results';

-- A centre manager sees results for their own centre; the scope is applied in the
-- controller, exactly as it is for attendance (03-rbac.md §4).
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.code = 'centre_manager' AND p.code = 'education.assessment.results';
