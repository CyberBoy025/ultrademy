<?php
declare(strict_types=1);

/**
 * Corporate training (README §46).
 *
 *   ORGANISATION → REQUEST → PROPOSAL → CONTRACT → PARTICIPANTS → DELIVERY → REPORT
 *
 * Two decisions hold the whole module together:
 *
 *   1. A contract is delivered through a real COHORT. Corporate participants get ordinary
 *      enrolments and flow through the same attendance, assessment and certificate
 *      machinery as everyone else. A parallel "corporate learning" path would mean two of
 *      everything, and the second one always rots.
 *   2. A contract is invoiced through the ordinary invoice spine, with
 *      `payable_type = 'corporate_contract'`. The fourth payable kind, and the fourth time
 *      that enum has absorbed a new business model without a new money path.
 */
final class Corporate
{
    public static function enabled(): bool
    {
        return (string) (Setting::get('corporate_enabled', '0') ?? '0') === '1';
    }

    public static function validityDays(): int
    {
        return max(1, (int) (Setting::get('corporate_proposal_validity_days', '30') ?? 30));
    }

    // -------------------------------------------------------------- organisations

    public static function organisations(?string $status = null): array
    {
        $sql = "SELECT o.*,
                       (SELECT COUNT(*) FROM contracts c WHERE c.organisation_id = o.id) AS contract_count,
                       (SELECT COUNT(*) FROM training_requests r WHERE r.organisation_id = o.id AND r.status IN ('new','reviewing','proposed')) AS open_requests,
                       (SELECT COALESCE(SUM(c.total_amount),0) FROM contracts c
                         WHERE c.organisation_id = o.id AND c.status IN ('active','delivering','completed')) AS contracted_value
                FROM organisations o WHERE 1=1";
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY o.name', $params)->fetchAll();
    }

    public static function findOrganisation(int $id): ?array
    {
        return Database::one('SELECT * FROM organisations WHERE id = :id', ['id' => $id]);
    }

    public static function createOrganisation(array $d): int
    {
        Database::query(
            'INSERT INTO organisations (name, slug, type, registration_no, industry, address_line, city, state, website, notes, status, created_by)
             VALUES (:n,:s,:t,:rc,:ind,:addr,:city,:state,:web,:notes,:status,:by)',
            [
                'n' => $d['name'], 's' => self::uniqueSlug($d['name']), 't' => $d['type'],
                'rc' => $d['registration_no'] ?: null, 'ind' => $d['industry'] ?: null,
                'addr' => $d['address_line'] ?: null, 'city' => $d['city'] ?: null,
                'state' => $d['state'] ?: null, 'web' => $d['website'] ?: null,
                'notes' => $d['notes'] ?: null, 'status' => $d['status'] ?? 'prospect', 'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    public static function updateOrganisation(int $id, array $d): void
    {
        Database::query(
            'UPDATE organisations SET name=:n, type=:t, registration_no=:rc, industry=:ind,
                    address_line=:addr, city=:city, state=:state, website=:web, notes=:notes, status=:status
             WHERE id=:id',
            [
                'n' => $d['name'], 't' => $d['type'], 'rc' => $d['registration_no'] ?: null,
                'ind' => $d['industry'] ?: null, 'addr' => $d['address_line'] ?: null,
                'city' => $d['city'] ?: null, 'state' => $d['state'] ?: null,
                'web' => $d['website'] ?: null, 'notes' => $d['notes'] ?: null,
                'status' => $d['status'], 'id' => $id,
            ]
        );
    }

    private static function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name))), '-');
        $base = $base !== '' ? mb_substr($base, 0, 100) : 'organisation';
        $slug = $base;
        $n = 2;
        while (Database::one('SELECT 1 FROM organisations WHERE slug = :s', ['s' => $slug])) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    public static function contacts(int $organisationId): array
    {
        return Database::all(
            'SELECT * FROM organisation_contacts WHERE organisation_id = :o ORDER BY is_primary DESC, name',
            ['o' => $organisationId]
        );
    }

    public static function addContact(int $organisationId, array $d): string
    {
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }
        try {
            Database::query(
                'INSERT INTO organisation_contacts (organisation_id, name, email, phone, job_title, is_primary, is_billing)
                 VALUES (:o,:n,:e,:p,:t,:pri,:bill)',
                [
                    'o' => $organisationId, 'n' => $d['name'], 'e' => mb_strtolower(trim($d['email'])),
                    'p' => $d['phone'] ?: null, 't' => $d['job_title'] ?: null,
                    'pri' => $d['is_primary'] ? 1 : 0, 'bill' => $d['is_billing'] ? 1 : 0,
                ]
            );
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return 'That contact is already on this organisation.';
            }
            throw $e;
        }
        return '';
    }

    /** The person invoices are addressed to — billing contact, else primary, else any. */
    public static function billingContact(int $organisationId): ?array
    {
        return Database::one(
            'SELECT * FROM organisation_contacts WHERE organisation_id = :o
             ORDER BY is_billing DESC, is_primary DESC, id LIMIT 1',
            ['o' => $organisationId]
        );
    }

    // ------------------------------------------------------------------- requests

    public static function requests(?string $status = null): array
    {
        $sql = "SELECT r.*, o.name AS org_name, pr.title AS programme_title, ct.name AS centre_name
                FROM training_requests r
                LEFT JOIN organisations o ON o.id = r.organisation_id
                LEFT JOIN programmes pr ON pr.id = r.programme_id
                LEFT JOIN centres ct ON ct.id = r.centre_id WHERE 1=1";
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' AND r.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY r.created_at DESC', $params)->fetchAll();
    }

    public static function findRequest(int $id): ?array
    {
        return Database::one(
            'SELECT r.*, o.name AS org_name, pr.title AS programme_title
             FROM training_requests r
             LEFT JOIN organisations o ON o.id = r.organisation_id
             LEFT JOIN programmes pr ON pr.id = r.programme_id
             WHERE r.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Records an enquiry. Called from the staff UI and from the public form.
     *
     * Never creates an organisation on its own. A public form that silently creates
     * company records fills the CRM with typos and duplicates; linking is a deliberate
     * act by whoever triages the request.
     */
    public static function createRequest(array $d, string $source = 'staff'): array
    {
        if (trim((string) $d['organisation_name']) === '') {
            return ['ok' => false, 'error' => 'Tell us which organisation this is for.', 'id' => null];
        }
        if (!filter_var($d['contact_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter a valid contact email address.', 'id' => null];
        }

        Database::query(
            'INSERT INTO training_requests
                (reference, organisation_id, organisation_name, contact_name, contact_email, contact_phone,
                 programme_id, participants, preferred_start, delivery_mode, centre_id, requirements, source, status)
             VALUES (:ref,:oid,:oname,:cn,:ce,:cp,:pid,:n,:start,:mode,:centre,:req,:src,\'new\')',
            [
                'ref' => DocumentNumber::next('TRQ'),
                'oid' => $d['organisation_id'] ?: null,
                'oname' => trim((string) $d['organisation_name']),
                'cn' => trim((string) $d['contact_name']),
                'ce' => mb_strtolower(trim((string) $d['contact_email'])),
                'cp' => $d['contact_phone'] ?: null,
                'pid' => $d['programme_id'] ?: null,
                'n' => $d['participants'] ?: null,
                'start' => $d['preferred_start'] ?: null,
                'mode' => $d['delivery_mode'] ?? 'unspecified',
                'centre' => $d['centre_id'] ?: null,
                'req' => $d['requirements'] ?: null,
                'src' => $source,
            ]
        );
        $id = Database::lastInsertId();
        Audit::log('corporate.request_created', 'training_requests', $id, null,
            ['organisation' => $d['organisation_name'], 'source' => $source]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    public static function updateRequestStatus(int $id, string $status, string $note = ''): void
    {
        $before = self::findRequest($id);
        Database::query(
            'UPDATE training_requests SET status = :s, lost_reason = :n WHERE id = :id',
            ['s' => $status, 'n' => $status === 'lost' && $note !== '' ? mb_substr($note, 0, 255) : null, 'id' => $id]
        );
        Audit::log('corporate.request_' . $status, 'training_requests', $id,
            ['status' => $before['status'] ?? null], ['status' => $status]);
    }

    public static function linkRequestToOrganisation(int $requestId, int $organisationId): void
    {
        Database::query(
            'UPDATE training_requests SET organisation_id = :o WHERE id = :id',
            ['o' => $organisationId, 'id' => $requestId]
        );
        Audit::log('corporate.request_linked', 'training_requests', $requestId, null, ['organisation_id' => $organisationId]);
    }

    // ------------------------------------------------------------------ proposals

    public static function proposals(?int $organisationId = null, ?string $status = null): array
    {
        $sql = 'SELECT p.*, o.name AS org_name, pr.title AS programme_title
                FROM proposals p
                JOIN organisations o ON o.id = p.organisation_id
                LEFT JOIN programmes pr ON pr.id = p.programme_id WHERE 1=1';
        $params = [];
        if ($organisationId !== null) {
            $sql .= ' AND p.organisation_id = ?';
            $params[] = $organisationId;
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY p.created_at DESC', $params)->fetchAll();
    }

    public static function findProposal(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, o.name AS org_name, pr.title AS programme_title, ct.name AS centre_name
             FROM proposals p
             JOIN organisations o ON o.id = p.organisation_id
             LEFT JOIN programmes pr ON pr.id = p.programme_id
             LEFT JOIN centres ct ON ct.id = p.centre_id
             WHERE p.id = :id',
            ['id' => $id]
        );
    }

    /** Seats × unit price, less discount. Never negative. */
    public static function proposalTotal(int $participants, int $unitAmount, int $discount): int
    {
        return max(0, ($participants * $unitAmount) - $discount);
    }

    public static function createProposal(array $d): array
    {
        $participants = max(1, (int) $d['participants']);
        $unit = max(0, (int) $d['unit_amount']);
        $discount = max(0, (int) $d['discount_amount']);
        $total = self::proposalTotal($participants, $unit, $discount);

        if ($discount > $participants * $unit) {
            return ['ok' => false, 'error' => 'The discount is larger than the total.', 'id' => null];
        }

        Database::query(
            'INSERT INTO proposals
                (reference, request_id, organisation_id, programme_id, title, scope, participants,
                 unit_amount, discount_amount, total_amount, currency, delivery_mode, centre_id,
                 starts_on, ends_on, valid_until, status, created_by)
             VALUES (:ref,:req,:org,:prog,:title,:scope,:n,:unit,:disc,:total,:cur,:mode,:centre,
                     :starts,:ends,:valid,\'draft\',:by)',
            [
                'ref' => DocumentNumber::next('PRP'),
                'req' => $d['request_id'] ?: null,
                'org' => (int) $d['organisation_id'],
                'prog' => $d['programme_id'] ?: null,
                'title' => $d['title'],
                'scope' => $d['scope'] ?: null,
                'n' => $participants, 'unit' => $unit, 'disc' => $discount, 'total' => $total,
                'cur' => 'NGN',
                'mode' => $d['delivery_mode'] ?? 'physical',
                'centre' => $d['centre_id'] ?: null,
                'starts' => $d['starts_on'] ?: null,
                'ends' => $d['ends_on'] ?: null,
                'valid' => $d['valid_until'] ?: date('Y-m-d', strtotime('+' . self::validityDays() . ' days')),
                'by' => Auth::id(),
            ]
        );
        $id = Database::lastInsertId();

        if (!empty($d['request_id'])) {
            self::updateRequestStatus((int) $d['request_id'], 'proposed');
        }
        Audit::log('corporate.proposal_created', 'proposals', $id, null,
            ['organisation_id' => $d['organisation_id'], 'total' => $total]);

        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    public static function setProposalStatus(int $id, string $status, string $note = ''): string
    {
        $proposal = self::findProposal($id);
        if (!$proposal) {
            return 'Proposal not found.';
        }
        if ($status === 'sent' && $proposal['status'] !== 'draft') {
            return 'Only a draft can be sent.';
        }
        if (in_array($status, ['accepted', 'declined'], true) && $proposal['status'] !== 'sent') {
            return 'Only a sent proposal can be accepted or declined.';
        }

        Database::query(
            "UPDATE proposals SET status = :s, decision_note = :n,
                    sent_at    = CASE WHEN :s2 = 'sent' THEN NOW() ELSE sent_at END,
                    decided_at = CASE WHEN :s3 IN ('accepted','declined') THEN NOW() ELSE decided_at END
             WHERE id = :id",
            ['s' => $status, 'n' => $note !== '' ? mb_substr($note, 0, 255) : null,
             's2' => $status, 's3' => $status, 'id' => $id]
        );
        Audit::log('corporate.proposal_' . $status, 'proposals', $id,
            ['status' => $proposal['status']], ['status' => $status, 'note' => $note]);

        if ($status === 'declined' && $proposal['request_id']) {
            self::updateRequestStatus((int) $proposal['request_id'], 'lost', $note);
        }
        return '';
    }

    /** A proposal past its validity date is not a live offer. */
    public static function expireStaleProposals(): int
    {
        $stmt = Database::query(
            "UPDATE proposals SET status = 'expired'
             WHERE status = 'sent' AND valid_until IS NOT NULL AND valid_until < CURDATE()"
        );
        return $stmt->rowCount();
    }

    // ------------------------------------------------------------------ contracts

    public static function contracts(?array $centreIds = null, ?string $status = null): array
    {
        $sql = 'SELECT c.*, o.name AS org_name, pr.title AS programme_title, ct.name AS centre_name,
                       (SELECT COUNT(*) FROM contract_participants cp WHERE cp.contract_id = c.id) AS participants,
                       (SELECT COUNT(*) FROM contract_participants cp WHERE cp.contract_id = c.id AND cp.status = \'active\') AS active_participants
                FROM contracts c
                JOIN organisations o ON o.id = c.organisation_id
                LEFT JOIN programmes pr ON pr.id = c.programme_id
                LEFT JOIN centres ct ON ct.id = c.centre_id WHERE 1=1';
        $params = [];
        if ($centreIds !== null) {
            if ($centreIds === []) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " AND c.centre_id IN ($ph)";
            $params = array_values($centreIds);
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY c.created_at DESC', $params)->fetchAll();
    }

    public static function findContract(int $id): ?array
    {
        return Database::one(
            'SELECT c.*, o.name AS org_name, o.id AS org_id, pr.title AS programme_title,
                    ct.name AS centre_name, co.name AS cohort_name, co.code AS cohort_code
             FROM contracts c
             JOIN organisations o ON o.id = c.organisation_id
             LEFT JOIN programmes pr ON pr.id = c.programme_id
             LEFT JOIN centres ct ON ct.id = c.centre_id
             LEFT JOIN cohorts co ON co.id = c.cohort_id
             WHERE c.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Turns an accepted proposal into a contract, a private cohort and an invoice, in one
     * transaction.
     *
     * All three or none. A contract with no cohort cannot be delivered; a cohort with no
     * invoice is unbilled training; and an invoice for a contract that failed to save is
     * a demand for money against nothing.
     */
    public static function createFromProposal(int $proposalId): array
    {
        $proposal = self::findProposal($proposalId);
        if (!$proposal) {
            return ['ok' => false, 'error' => 'Proposal not found.', 'id' => null];
        }
        if ($proposal['status'] !== 'accepted') {
            return ['ok' => false, 'error' => 'Only an accepted proposal becomes a contract.', 'id' => null];
        }
        if (Database::one('SELECT 1 FROM contracts WHERE proposal_id = :p', ['p' => $proposalId])) {
            return ['ok' => false, 'error' => 'This proposal already has a contract.', 'id' => null];
        }
        if (empty($proposal['programme_id'])) {
            return ['ok' => false, 'error' => 'Attach a programme to the proposal before raising a contract — the cohort needs one.', 'id' => null];
        }

        $billing = self::billingContact((int) $proposal['organisation_id']);
        if (!$billing) {
            return ['ok' => false, 'error' => 'Add a contact for this organisation first — the invoice needs somebody to address.', 'id' => null];
        }

        try {
            $id = Database::transaction(static function () use ($proposal, $billing): int {
                $reference = DocumentNumber::next('CTR');

                // The cohort that will actually deliver it. Private to this client, but an
                // ordinary cohort in every other respect.
                $cohortId = Cohort::create(
                    (int) $proposal['programme_id'],
                    $proposal['centre_id'] !== null ? (int) $proposal['centre_id'] : null,
                    $reference,
                    $proposal['org_name'] . ' — ' . $proposal['title'],
                    $proposal['starts_on'] ?: null,
                    $proposal['ends_on'] ?: null,
                    (int) $proposal['participants']
                );

                Database::query(
                    'INSERT INTO contracts
                        (reference, proposal_id, organisation_id, programme_id, cohort_id, title,
                         participants_cap, total_amount, currency, centre_id, starts_on, ends_on, status, created_by)
                     VALUES (:ref,:prop,:org,:prog,:cohort,:title,:cap,:total,:cur,:centre,:starts,:ends,\'draft\',:by)',
                    [
                        'ref' => $reference, 'prop' => (int) $proposal['id'],
                        'org' => (int) $proposal['organisation_id'],
                        'prog' => (int) $proposal['programme_id'], 'cohort' => $cohortId,
                        'title' => $proposal['title'], 'cap' => (int) $proposal['participants'],
                        'total' => (int) $proposal['total_amount'], 'cur' => $proposal['currency'],
                        'centre' => $proposal['centre_id'] !== null ? (int) $proposal['centre_id'] : null,
                        'starts' => $proposal['starts_on'] ?: null, 'ends' => $proposal['ends_on'] ?: null,
                        'by' => Auth::id(),
                    ]
                );
                $contractId = Database::lastInsertId();

                // Billed to the organisation's contact, through the ordinary invoice spine.
                $userId = $billing['user_id'] !== null
                    ? (int) $billing['user_id']
                    : self::resolveContactUser((int) $billing['id'], (string) $billing['email'], (string) $billing['name']);

                Invoice::issue(
                    $userId,
                    [[
                        'description' => $proposal['title'] . ' — ' . $proposal['participants'] . ' participant(s)',
                        'quantity' => 1,
                        'unit_amount' => (int) $proposal['total_amount'],
                    ]],
                    'corporate_contract',
                    $contractId,
                    $proposal['centre_id'] !== null ? (int) $proposal['centre_id'] : null,
                    null,
                    0,
                    $proposal['currency']
                );

                return $contractId;
            });
        } catch (Throwable $e) {
            error_log('[corporate] contract creation: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not raise the contract. Nothing was saved.', 'id' => null];
        }

        if ($proposal['request_id']) {
            self::updateRequestStatus((int) $proposal['request_id'], 'won');
        }
        Audit::log('corporate.contract_created', 'contracts', $id, null,
            ['proposal_id' => $proposalId, 'total' => $proposal['total_amount']]);

        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    public static function setContractStatus(int $id, string $status): string
    {
        $contract = self::findContract($id);
        if (!$contract) {
            return 'Contract not found.';
        }
        Database::query('UPDATE contracts SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
        Audit::log('corporate.contract_' . $status, 'contracts', $id,
            ['status' => $contract['status']], ['status' => $status],
            $contract['centre_id'] !== null ? (int) $contract['centre_id'] : null);
        return '';
    }

    public static function contractInvoice(int $contractId): ?array
    {
        return Database::one(
            "SELECT * FROM invoices WHERE payable_type = 'corporate_contract' AND payable_id = :id",
            ['id' => $contractId]
        );
    }

    // --------------------------------------------------------------- participants

    public static function participants(int $contractId): array
    {
        return Database::all(
            'SELECT cp.*, e.student_no, e.status AS enrolment_status
             FROM contract_participants cp
             LEFT JOIN enrolments e ON e.id = cp.enrolment_id
             WHERE cp.contract_id = :c ORDER BY cp.name',
            ['c' => $contractId]
        );
    }

    public static function findParticipantByToken(string $token): ?array
    {
        return Database::one(
            'SELECT cp.*, c.title AS contract_title, c.cohort_id, o.name AS org_name
             FROM contract_participants cp
             JOIN contracts c ON c.id = cp.contract_id
             JOIN organisations o ON o.id = c.organisation_id
             WHERE cp.invite_token = :t',
            ['t' => $token]
        );
    }

    /**
     * Nominates an employee. Does NOT create an account.
     *
     * An employer supplying a name and an email is not that person's consent to hold an
     * account, and it is not proof the address is theirs. The account appears when they
     * click the link in their own inbox.
     */
    public static function addParticipant(int $contractId, array $d): string
    {
        $contract = self::findContract($contractId);
        if (!$contract) {
            return 'Contract not found.';
        }
        if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }

        $used = (int) (Database::one(
            "SELECT COUNT(*) c FROM contract_participants WHERE contract_id = :c AND status <> 'withdrawn'",
            ['c' => $contractId]
        )['c'] ?? 0);
        if ($used >= (int) $contract['participants_cap']) {
            return 'All ' . (int) $contract['participants_cap'] . ' purchased seats are taken. Raise the cap on the contract to add more.';
        }

        try {
            Database::query(
                'INSERT INTO contract_participants (contract_id, name, email, phone, job_title, status)
                 VALUES (:c,:n,:e,:p,:t,\'nominated\')',
                [
                    'c' => $contractId, 'n' => trim((string) $d['name']),
                    'e' => mb_strtolower(trim((string) $d['email'])),
                    'p' => $d['phone'] ?: null, 't' => $d['job_title'] ?: null,
                ]
            );
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return 'That person is already nominated on this contract.';
            }
            throw $e;
        }
        Audit::log('corporate.participant_added', 'contract_participants', Database::lastInsertId(), null,
            ['contract_id' => $contractId]);
        return '';
    }

    /** Issues an invitation token and notifies. Re-invites are allowed; the token rotates. */
    public static function invite(int $participantId): string
    {
        $p = Database::one('SELECT * FROM contract_participants WHERE id = :id', ['id' => $participantId]);
        if (!$p) {
            return 'Participant not found.';
        }
        if ($p['status'] === 'withdrawn') {
            return 'That participant has been withdrawn.';
        }

        $token = bin2hex(random_bytes(16));
        Database::query(
            "UPDATE contract_participants SET invite_token = :t, invited_at = NOW(),
                    status = CASE WHEN status = 'nominated' THEN 'invited' ELSE status END
             WHERE id = :id",
            ['t' => $token, 'id' => $participantId]
        );
        Audit::log('corporate.participant_invited', 'contract_participants', $participantId, null, []);
        return '';
    }

    /**
     * The participant accepts: their account is created or matched, and they are enrolled
     * into the contract's cohort.
     *
     * Idempotent — a second click on the same link returns the existing enrolment rather
     * than burning another seat.
     */
    public static function acceptInvitation(string $token, string $password): array
    {
        $p = self::findParticipantByToken($token);
        if (!$p) {
            return ['ok' => false, 'error' => 'That invitation link is not valid.', 'user_id' => null];
        }
        if ($p['accepted_at'] !== null && $p['user_id'] !== null) {
            return ['ok' => true, 'error' => null, 'user_id' => (int) $p['user_id']];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Choose a password of at least 8 characters.', 'user_id' => null];
        }
        if (empty($p['cohort_id'])) {
            return ['ok' => false, 'error' => 'This training is not ready yet. Please contact your organiser.', 'user_id' => null];
        }

        try {
            $userId = Database::transaction(static function () use ($p, $password): int {
                $existing = Database::one('SELECT id, status FROM users WHERE email = :e', ['e' => $p['email']]);

                if ($existing) {
                    $userId = (int) $existing['id'];
                    // An existing account keeps its own password. Letting an invitation
                    // link set the password of an account that already exists would be an
                    // account takeover: anyone who can guess an employee's address could
                    // have their employer nominate them.
                    if ($existing['status'] === 'pending') {
                        Database::query(
                            "UPDATE users SET password_hash = :h, status = 'active', email_verified_at = NOW()
                             WHERE id = :id AND status = 'pending'",
                            ['h' => password_hash($password, PASSWORD_DEFAULT), 'id' => $userId]
                        );
                    }
                } else {
                    Database::query(
                        "INSERT INTO users (email, password_hash, status, email_verified_at)
                         VALUES (:e,:h,'active',NOW())",
                        ['e' => $p['email'], 'h' => password_hash($password, PASSWORD_DEFAULT)]
                    );
                    $userId = Database::lastInsertId();
                    $parts = preg_split('/\s+/', trim((string) $p['name']), 2) ?: [];
                    Database::query(
                        'INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)',
                        ['u' => $userId, 'f' => $parts[0] ?? $p['name'], 'l' => $parts[1] ?? '']
                    );
                }

                // The seat is already paid for by the employer, so the enrolment is active
                // rather than pending_payment.
                $enrolmentId = Enrolment::existsFor($userId, (int) $p['cohort_id'])
                    ? (int) Database::one(
                        'SELECT id FROM enrolments WHERE user_id = :u AND cohort_id = :c',
                        ['u' => $userId, 'c' => (int) $p['cohort_id']]
                    )['id']
                    : Enrolment::create($userId, (int) $p['cohort_id'], null, 'active');

                Database::query(
                    "UPDATE contract_participants
                     SET user_id = :u, enrolment_id = :e, accepted_at = NOW(), status = 'active', invite_token = NULL
                     WHERE id = :id",
                    ['u' => $userId, 'e' => $enrolmentId, 'id' => (int) $p['id']]
                );
                return $userId;
            });
        } catch (Throwable $e) {
            error_log('[corporate] invitation acceptance: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Something went wrong setting up your account. Please contact your organiser.', 'user_id' => null];
        }

        Audit::log('corporate.participant_accepted', 'contract_participants', (int) $p['id'], null, ['user_id' => $userId]);
        return ['ok' => true, 'error' => null, 'user_id' => $userId];
    }

    public static function withdrawParticipant(int $participantId): void
    {
        $p = Database::one('SELECT * FROM contract_participants WHERE id = :id', ['id' => $participantId]);
        if (!$p) {
            return;
        }
        Database::query("UPDATE contract_participants SET status = 'withdrawn', invite_token = NULL WHERE id = :id", ['id' => $participantId]);
        if ($p['enrolment_id'] !== null) {
            Enrolment::setStatus((int) $p['enrolment_id'], 'withdrawn');
        }
        Audit::log('corporate.participant_withdrawn', 'contract_participants', $participantId,
            ['status' => $p['status']], ['status' => 'withdrawn']);
    }

    // --------------------------------------------------------------------- report

    /**
     * The corporate report §46 ends with: how each nominated employee is doing.
     *
     * Attendance and assessment come from the ordinary tables, because corporate
     * participants are ordinary students — which is the whole point of delivering through
     * a real cohort.
     */
    public static function report(int $contractId): array
    {
        return Database::all(
            "SELECT cp.name, cp.email, cp.job_title, cp.status, e.student_no, e.status AS enrolment_status,
                    (SELECT COUNT(*) FROM attendance_records ar WHERE ar.enrolment_id = e.id) AS sessions_marked,
                    (SELECT ROUND(SUM(ar.status IN ('present','late')) * 100 / NULLIF(COUNT(ar.id),0), 1)
                       FROM attendance_records ar WHERE ar.enrolment_id = e.id) AS attendance_rate,
                    (SELECT ROUND(AVG(t.score_percent), 1) FROM assessment_attempts t
                       WHERE t.user_id = cp.user_id AND t.status = 'graded') AS avg_assessment,
                    (SELECT COUNT(*) FROM certificates ce WHERE ce.user_id = cp.user_id AND ce.revoked_at IS NULL) AS certificates
             FROM contract_participants cp
             LEFT JOIN enrolments e ON e.id = cp.enrolment_id
             WHERE cp.contract_id = :c
             ORDER BY cp.name",
            ['c' => $contractId]
        );
    }

    /** Pipeline counts for the corporate dashboard. */
    public static function pipeline(): array
    {
        $one = static fn(string $sql): int => (int) (Database::one($sql)['c'] ?? 0);
        return [
            'new_requests'     => $one("SELECT COUNT(*) c FROM training_requests WHERE status = 'new'"),
            'open_requests'    => $one("SELECT COUNT(*) c FROM training_requests WHERE status IN ('new','reviewing','proposed')"),
            'sent_proposals'   => $one("SELECT COUNT(*) c FROM proposals WHERE status = 'sent'"),
            'proposal_value'   => (int) (Database::one("SELECT COALESCE(SUM(total_amount),0) c FROM proposals WHERE status = 'sent'")['c'] ?? 0),
            'active_contracts' => $one("SELECT COUNT(*) c FROM contracts WHERE status IN ('active','delivering')"),
            'contract_value'   => (int) (Database::one("SELECT COALESCE(SUM(total_amount),0) c FROM contracts WHERE status IN ('active','delivering','completed')")['c'] ?? 0),
            'participants'     => $one("SELECT COUNT(*) c FROM contract_participants WHERE status = 'active'"),
        ];
    }

    /** Resolves a contact to a user row so an invoice can be addressed to them. */
    private static function resolveContactUser(int $contactId, string $email, string $name): int
    {
        $email = mb_strtolower(trim($email));
        $existing = Database::one('SELECT id FROM users WHERE email = :e', ['e' => $email]);
        if ($existing) {
            Database::query('UPDATE organisation_contacts SET user_id = :u WHERE id = :id',
                ['u' => (int) $existing['id'], 'id' => $contactId]);
            return (int) $existing['id'];
        }
        Database::query(
            "INSERT INTO users (email, password_hash, status) VALUES (:e,:p,'pending')",
            ['e' => $email, 'p' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]
        );
        $userId = Database::lastInsertId();
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];
        Database::query('INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)',
            ['u' => $userId, 'f' => $parts[0] ?? 'Contact', 'l' => $parts[1] ?? '']);
        Database::query('UPDATE organisation_contacts SET user_id = :u WHERE id = :id',
            ['u' => $userId, 'id' => $contactId]);
        return $userId;
    }
}
