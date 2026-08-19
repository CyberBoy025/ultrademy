-- A class group cannot legitimately have two sessions starting at the same instant —
-- that is always a duplicate. Without this key, seed.php's INSERT IGNORE had nothing to
-- match on and re-running the seed multiplied the timetable.
--
-- Deduplicate first (keeping the lowest id, which is the row attendance_records already
-- point at), then add the constraint that stops it recurring.

DELETE cs FROM class_sessions cs
JOIN (
    SELECT class_group_id, starts_at, MIN(id) AS keep_id
    FROM class_sessions
    GROUP BY class_group_id, starts_at
    HAVING COUNT(*) > 1
) dupes
  ON cs.class_group_id = dupes.class_group_id
 AND cs.starts_at      = dupes.starts_at
 AND cs.id            <> dupes.keep_id;

ALTER TABLE class_sessions
  ADD UNIQUE KEY uq_session_group_start (class_group_id, starts_at);
