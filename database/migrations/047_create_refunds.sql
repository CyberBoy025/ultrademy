-- §8: create and approve are different permissions held by different roles. The
-- accountant raises a refund; management approves it. Same two-person rule as payment
-- verification.
CREATE TABLE IF NOT EXISTS refunds (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id   BIGINT UNSIGNED NOT NULL,
    amount       BIGINT UNSIGNED NOT NULL,
    currency     CHAR(3) NOT NULL DEFAULT 'NGN',
    reason       VARCHAR(255) NOT NULL,
    status       ENUM('requested','approved','rejected','processed') NOT NULL DEFAULT 'requested',
    requested_by BIGINT UNSIGNED NULL,
    approved_by  BIGINT UNSIGNED NULL,
    decided_at   TIMESTAMP NULL DEFAULT NULL,
    decision_note VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_refunds_payment (payment_id),
    KEY ix_refunds_status (status),
    CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
    CONSTRAINT fk_refunds_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_refunds_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
