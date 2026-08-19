-- Richer than the generic `notifications` email-queue row (brief §26: recipient, subject,
-- template, trigger, delivery status, related application). Actual delivery is still
-- capped by the platform-wide "no mail transport configured" gap — this table records
-- correctly queued/sent/failed state regardless of when that gap is closed.
CREATE TABLE IF NOT EXISTS recruitment_email_logs (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_application_id   BIGINT UNSIGNED NULL,
    recipient_email      VARCHAR(190) NOT NULL,
    subject              VARCHAR(255) NOT NULL,
    template_code        VARCHAR(60) NULL,
    trigger_type         VARCHAR(60) NOT NULL,
    status               ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    error                VARCHAR(255) NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at              TIMESTAMP NULL DEFAULT NULL,
    KEY ix_recruitment_email_logs_status (status),
    KEY ix_recruitment_email_logs_application (job_application_id),
    CONSTRAINT fk_recruitment_email_logs_application FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
