CREATE TABLE IF NOT EXISTS payment_proofs (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id     BIGINT UNSIGNED NOT NULL,
    stored_name    VARCHAR(255) NOT NULL COMMENT 'random filename under storage/app/proofs',
    original_name  VARCHAR(255) NOT NULL,
    mime_type      VARCHAR(100) NOT NULL,
    size_bytes     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by    BIGINT UNSIGNED NULL,
    uploaded_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_proof_stored_name (stored_name),
    KEY ix_proofs_payment (payment_id),
    CONSTRAINT fk_proofs_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_proofs_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
