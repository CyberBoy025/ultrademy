-- An accepted proposal becomes a contract: the thing that is actually delivered and
-- invoiced.
--
-- `cohort_id` links the contract to a real cohort, so corporate participants flow through
-- exactly the same enrolment, attendance, assessment and certificate machinery as anyone
-- else. A parallel "corporate learning" path would mean two of everything.
CREATE TABLE IF NOT EXISTS contracts (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference        VARCHAR(30) NOT NULL,
    proposal_id      BIGINT UNSIGNED NULL,
    organisation_id  BIGINT UNSIGNED NOT NULL,
    programme_id     BIGINT UNSIGNED NULL,
    cohort_id        BIGINT UNSIGNED NULL COMMENT 'the private cohort delivering this contract',
    title            VARCHAR(200) NOT NULL,
    participants_cap SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'seats purchased',
    total_amount     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    currency         CHAR(3) NOT NULL DEFAULT 'NGN',
    centre_id        BIGINT UNSIGNED NULL COMMENT 'NULL = online (§31)',
    starts_on        DATE NULL,
    ends_on          DATE NULL,
    signed_at        DATE NULL,
    status           ENUM('draft','active','delivering','completed','cancelled') NOT NULL DEFAULT 'draft',
    created_by       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_contract_reference (reference),
    KEY ix_contract_org (organisation_id, status),
    CONSTRAINT fk_contract_proposal  FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE SET NULL,
    CONSTRAINT fk_contract_org       FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE RESTRICT,
    CONSTRAINT fk_contract_programme FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE SET NULL,
    CONSTRAINT fk_contract_cohort    FOREIGN KEY (cohort_id) REFERENCES cohorts(id) ON DELETE SET NULL,
    CONSTRAINT fk_contract_centre    FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL,
    CONSTRAINT fk_contract_creator   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
