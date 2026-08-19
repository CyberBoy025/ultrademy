-- Deferred from Phase 4: class_sessions.lesson_id was created as a bare nullable column
-- because the lessons table did not exist yet (see 09-core-foundation.md §2). It does now,
-- so the constraint that was always intended can be added.
--
-- ON DELETE SET NULL: deleting a lesson must not delete the timetable entry that taught it.

ALTER TABLE class_sessions
  ADD CONSTRAINT fk_class_sessions_lesson
      FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL;
