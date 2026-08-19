CREATE TABLE IF NOT EXISTS attendance_records (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_session_id  BIGINT UNSIGNED NOT NULL,
    enrolment_id      BIGINT UNSIGNED NOT NULL,
    status            ENUM('present','late','absent','excused') NOT NULL,
    marked_by         BIGINT UNSIGNED NULL,
    marked_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance_session_enrolment (class_session_id, enrolment_id),
    KEY ix_attendance_session (class_session_id),
    CONSTRAINT fk_attendance_session FOREIGN KEY (class_session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_enrolment FOREIGN KEY (enrolment_id) REFERENCES enrolments(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_marked_by FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
