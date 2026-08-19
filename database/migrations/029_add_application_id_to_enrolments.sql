-- Deferred from Phase 5: the column was pointless before an applications table existed
-- (see 10-centres-operations.md §6). Nullable because 02-data-model.md §4 explicitly
-- allows direct enrolment — someone who walks into a hub and registers at the desk has
-- no online application.

ALTER TABLE enrolments
  ADD COLUMN application_id BIGINT UNSIGNED NULL AFTER centre_id,
  ADD CONSTRAINT fk_enrolments_application
      FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL;

-- One approved application produces at most one enrolment.
ALTER TABLE enrolments
  ADD UNIQUE KEY uq_enrolment_application (application_id);
