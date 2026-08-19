-- §5 "Webhook idempotency". Gateways retry; without the unique constraint a retried
-- charge.success credits the invoice twice.
--
-- Processing order matters: insert this row FIRST (the unique key rejects duplicates),
-- then act on it. Signature failures are stored with signature_valid = 0 and never
-- processed — they are also a security signal worth alerting on.
CREATE TABLE IF NOT EXISTS webhook_events (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider         VARCHAR(30) NOT NULL,
    event_id         VARCHAR(160) NOT NULL COMMENT 'the gateway event/charge identifier',
    event_type       VARCHAR(60) NULL,
    payload          JSON NULL,
    signature_valid  TINYINT(1) NOT NULL DEFAULT 0,
    received_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at     TIMESTAMP NULL DEFAULT NULL,
    error            VARCHAR(255) NULL,
    remote_ip        VARCHAR(45) NULL,
    UNIQUE KEY uq_webhook_event (provider, event_id),
    KEY ix_webhook_unprocessed (processed_at, signature_valid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
