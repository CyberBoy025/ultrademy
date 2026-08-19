-- Many-to-many on purpose (02-data-model.md §3): one course can serve several
-- programmes without being duplicated. A programme is what you apply and pay for; a
-- course is teachable content.
CREATE TABLE IF NOT EXISTS programme_courses (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    programme_id  BIGINT UNSIGNED NOT NULL,
    course_id     BIGINT UNSIGNED NOT NULL,
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_required   TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_programme_course (programme_id, course_id),
    CONSTRAINT fk_programme_courses_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    CONSTRAINT fk_programme_courses_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
