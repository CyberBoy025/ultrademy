-- `uq_user_role_centre (user_id, role_id, centre_id)` does not do what it looks like it
-- does. In MySQL/MariaDB, NULLs never collide in a UNIQUE index, so every GLOBAL role
-- grant (centre_id IS NULL) could be inserted repeatedly — INSERT IGNORE had nothing to
-- match on. Re-running seed.php six times produced six copies of every global grant.
-- Centre-scoped grants were unaffected, which is why this stayed hidden.
--
-- Fix: collapse NULL to a sentinel in a generated column and key on that instead.

DELETE ur FROM user_roles ur
JOIN (
    SELECT user_id, role_id, centre_id, MIN(id) AS keep_id
    FROM user_roles
    GROUP BY user_id, role_id, centre_id
    HAVING COUNT(*) > 1
) dupes
  ON ur.user_id = dupes.user_id
 AND ur.role_id = dupes.role_id
 AND (ur.centre_id = dupes.centre_id OR (ur.centre_id IS NULL AND dupes.centre_id IS NULL))
 AND ur.id <> dupes.keep_id;

-- Order matters: the FK on user_id is using uq_user_role_centre as its supporting index,
-- so the replacement (which also leads with user_id) has to exist BEFORE the old one can
-- be dropped, or MariaDB refuses with errno 1553.
ALTER TABLE user_roles
  ADD COLUMN centre_key BIGINT UNSIGNED AS (IFNULL(centre_id, 0)) STORED,
  ADD UNIQUE KEY uq_user_role_scope (user_id, role_id, centre_key);

ALTER TABLE user_roles DROP INDEX uq_user_role_centre;
