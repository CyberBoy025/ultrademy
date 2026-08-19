CREATE TABLE IF NOT EXISTS invoice_lines (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id   BIGINT UNSIGNED NOT NULL,
    description  VARCHAR(255) NOT NULL,
    quantity     INT UNSIGNED NOT NULL DEFAULT 1,
    unit_amount  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'minor units',
    line_amount  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'quantity * unit_amount, stored',
    sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY ix_lines_invoice (invoice_id),
    CONSTRAINT fk_lines_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
