CREATE TABLE IF NOT EXISTS certificates (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    serial        VARCHAR(40) NOT NULL COMMENT 'public verification key — Decision 5',
    user_id       BIGINT UNSIGNED NOT NULL,
    course_id     BIGINT UNSIGNED NULL COMMENT 'a course certificate',
    programme_id  BIGINT UNSIGNED NULL COMMENT 'or a whole-programme certificate',
    enrolment_id  BIGINT UNSIGNED NULL,
    title         VARCHAR(200) NOT NULL,
    issued_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    issued_by     BIGINT UNSIGNED NULL,
    revoked_at    TIMESTAMP NULL DEFAULT NULL COMMENT 'revoked rather than deleted, so a serial never silently 404s',
    UNIQUE KEY uq_certificates_serial (serial),
    KEY ix_certificates_user (user_id),
    CONSTRAINT fk_certificates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_certificates_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificates_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificates_enrolment FOREIGN KEY (enrolment_id) REFERENCES enrolments(id) ON DELETE SET NULL,
    CONSTRAINT fk_certificates_issuer FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
