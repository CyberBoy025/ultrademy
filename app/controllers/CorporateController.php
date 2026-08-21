<?php
declare(strict_types=1);

/**
 * Corporate training — the §46 pipeline, staff side.
 *
 * Selling is separated from committing: `corporate.proposal.manage` lets someone quote,
 * `corporate.contract.approve` lets them bind the company. The same approve/execute split
 * that runs through the rest of the system.
 */
final class CorporateController
{
    public static function index(): void
    {
        Auth::requirePermission('corporate.request.manage');
        Corporate::expireStaleProposals();

        $main = View::render('corporate/index', [
            'pipeline'  => Corporate::pipeline(),
            'requests'  => array_slice(Corporate::requests(), 0, 12),
            'proposals' => array_slice(Corporate::proposals(), 0, 10),
            'contracts' => array_slice(Corporate::contracts(Auth::scopeCentres('corporate.report.view')), 0, 10),
            'enabled'   => Corporate::enabled(),
        ]);
        View::shell('corporate', 'Corporate Training', $main);
    }

    // -------------------------------------------------------------- organisations

    public static function organisations(): void
    {
        Auth::requirePermission('corporate.organisation.manage');
        $main = View::render('corporate/organisations', [
            'organisations' => Corporate::organisations((string) ($_GET['status'] ?? '') ?: null),
            'status'        => (string) ($_GET['status'] ?? ''),
        ]);
        View::shell('corporate', 'Organisations', $main);
    }

    public static function organisationStore(): void
    {
        Auth::requirePermission('corporate.organisation.manage');
        Csrf::requireValid();
        if (trim((string) $_POST['name']) === '') {
            Session::flash('error', 'An organisation needs a name.');
            header('Location: app.php?r=corporate.organisations');
            exit;
        }
        $id = Corporate::createOrganisation(self::organisationForm());
        Audit::log('corporate.organisation_created', 'organisations', $id, null, ['name' => $_POST['name']]);
        Session::flash('success', 'Organisation added.');
        header('Location: app.php?r=corporate.organisation&id=' . $id);
        exit;
    }

    public static function organisation(): void
    {
        Auth::requirePermission('corporate.organisation.manage');
        $org = Corporate::findOrganisation((int) ($_GET['id'] ?? 0));
        if (!$org) {
            http_response_code(404);
            echo 'Organisation not found.';
            return;
        }
        $main = View::render('corporate/organisation', [
            'org'       => $org,
            'contacts'  => Corporate::contacts((int) $org['id']),
            'proposals' => Corporate::proposals((int) $org['id']),
            'contracts' => array_values(array_filter(
                Corporate::contracts(null),
                static fn(array $c): bool => (int) $c['organisation_id'] === (int) $org['id']
            )),
        ]);
        View::shell('corporate', $org['name'], $main);
    }

    public static function organisationUpdate(): void
    {
        Auth::requirePermission('corporate.organisation.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        Corporate::updateOrganisation($id, self::organisationForm());
        Audit::log('corporate.organisation_updated', 'organisations', $id, null, ['name' => $_POST['name']]);
        Session::flash('success', 'Saved.');
        header('Location: app.php?r=corporate.organisation&id=' . $id);
        exit;
    }

    public static function contactStore(): void
    {
        Auth::requirePermission('corporate.organisation.manage');
        Csrf::requireValid();
        $orgId = (int) $_POST['organisation_id'];
        $error = Corporate::addContact($orgId, [
            'name'       => trim((string) $_POST['name']),
            'email'      => trim((string) $_POST['email']),
            'phone'      => trim((string) ($_POST['phone'] ?? '')),
            'job_title'  => trim((string) ($_POST['job_title'] ?? '')),
            'is_primary' => isset($_POST['is_primary']),
            'is_billing' => isset($_POST['is_billing']),
        ]);
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Contact added.' : $error);
        header('Location: app.php?r=corporate.organisation&id=' . $orgId);
        exit;
    }

    // ------------------------------------------------------------------- requests

    public static function requests(): void
    {
        Auth::requirePermission('corporate.request.manage');
        $main = View::render('corporate/requests', [
            'requests' => Corporate::requests((string) ($_GET['status'] ?? '') ?: null),
            'status'   => (string) ($_GET['status'] ?? ''),
        ]);
        View::shell('corporate', 'Training Requests', $main);
    }

    public static function request(): void
    {
        Auth::requirePermission('corporate.request.manage');
        $request = Corporate::findRequest((int) ($_GET['id'] ?? 0));
        if (!$request) {
            http_response_code(404);
            echo 'Request not found.';
            return;
        }
        $main = View::render('corporate/request', [
            'request'       => $request,
            'organisations' => Corporate::organisations(),
            'programmes'    => Programme::all(true),
            'centres'       => Centre::all(),
            'proposals'     => $request['organisation_id'] ? Corporate::proposals((int) $request['organisation_id']) : [],
            'canPropose'    => Auth::can('corporate.proposal.manage'),
        ]);
        View::shell('corporate', $request['reference'], $main);
    }

    public static function requestStore(): void
    {
        Auth::requirePermission('corporate.request.manage');
        Csrf::requireValid();
        $result = Corporate::createRequest(self::requestForm(), 'staff');
        if (!$result['ok']) {
            Session::flash('error', (string) $result['error']);
            header('Location: app.php?r=corporate.requests');
            exit;
        }
        Session::flash('success', 'Request logged.');
        header('Location: app.php?r=corporate.request&id=' . $result['id']);
        exit;
    }

    public static function requestUpdate(): void
    {
        Auth::requirePermission('corporate.request.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        if (($_POST['organisation_id'] ?? '') !== '') {
            Corporate::linkRequestToOrganisation($id, (int) $_POST['organisation_id']);
        }
        if (($_POST['status'] ?? '') !== '') {
            Corporate::updateRequestStatus($id, (string) $_POST['status'], trim((string) ($_POST['note'] ?? '')));
        }
        Session::flash('success', 'Updated.');
        header('Location: app.php?r=corporate.request&id=' . $id);
        exit;
    }

    // ------------------------------------------------------------------ proposals

    public static function proposalStore(): void
    {
        Auth::requirePermission('corporate.proposal.manage');
        Csrf::requireValid();

        if (($_POST['organisation_id'] ?? '') === '') {
            Session::flash('error', 'Link the request to an organisation before quoting — a proposal needs somebody to send it to.');
            header('Location: app.php?r=corporate.request&id=' . (int) $_POST['request_id']);
            exit;
        }

        $result = Corporate::createProposal([
            'request_id'      => (int) ($_POST['request_id'] ?? 0) ?: null,
            'organisation_id' => (int) $_POST['organisation_id'],
            'programme_id'    => (int) ($_POST['programme_id'] ?? 0) ?: null,
            'title'           => trim((string) $_POST['title']),
            'scope'           => trim((string) ($_POST['scope'] ?? '')),
            'participants'    => (int) ($_POST['participants'] ?? 1),
            'unit_amount'     => Money::toMinor((string) ($_POST['unit_amount'] ?? '0')),
            'discount_amount' => Money::toMinor((string) ($_POST['discount_amount'] ?? '0')),
            'delivery_mode'   => (string) ($_POST['delivery_mode'] ?? 'physical'),
            'centre_id'       => (int) ($_POST['centre_id'] ?? 0) ?: null,
            'starts_on'       => $_POST['starts_on'] ?: null,
            'ends_on'         => $_POST['ends_on'] ?: null,
            'valid_until'     => $_POST['valid_until'] ?: null,
        ]);

        if (!$result['ok']) {
            Session::flash('error', (string) $result['error']);
            header('Location: app.php?r=corporate.request&id=' . (int) $_POST['request_id']);
            exit;
        }
        Session::flash('success', 'Proposal drafted.');
        header('Location: app.php?r=corporate.proposal&id=' . $result['id']);
        exit;
    }

    public static function proposal(): void
    {
        Auth::requirePermission('corporate.proposal.manage');
        $proposal = Corporate::findProposal((int) ($_GET['id'] ?? 0));
        if (!$proposal) {
            http_response_code(404);
            echo 'Proposal not found.';
            return;
        }
        $contract = Database::one('SELECT id FROM contracts WHERE proposal_id = :p', ['p' => (int) $proposal['id']]);
        $main = View::render('corporate/proposal', [
            'proposal'   => $proposal,
            'contacts'   => Corporate::contacts((int) $proposal['organisation_id']),
            'contractId' => $contract['id'] ?? null,
            'canSign'    => Auth::can('corporate.contract.approve'),
        ]);
        View::shell('corporate', $proposal['reference'], $main);
    }

    public static function proposalStatus(): void
    {
        Auth::requirePermission('corporate.proposal.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $error = Corporate::setProposalStatus($id, (string) $_POST['status'], trim((string) ($_POST['note'] ?? '')));
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Updated.' : $error);
        header('Location: app.php?r=corporate.proposal&id=' . $id);
        exit;
    }

    // ------------------------------------------------------------------ contracts

    public static function contracts(): void
    {
        Auth::requirePermission('corporate.report.view');
        $main = View::render('corporate/contracts', [
            'contracts' => Corporate::contracts(Auth::scopeCentres('corporate.report.view'), (string) ($_GET['status'] ?? '') ?: null),
            'status'    => (string) ($_GET['status'] ?? ''),
        ]);
        View::shell('corporate', 'Contracts', $main);
    }

    /** Turning an accepted proposal into a contract commits the company — management only. */
    public static function contractCreate(): void
    {
        Auth::requirePermission('corporate.contract.approve');
        Csrf::requireValid();
        $result = Corporate::createFromProposal((int) $_POST['proposal_id']);
        if (!$result['ok']) {
            Session::flash('error', (string) $result['error']);
            header('Location: app.php?r=corporate.proposal&id=' . (int) $_POST['proposal_id']);
            exit;
        }
        Session::flash('success', 'Contract raised, private cohort created and the invoice issued.');
        header('Location: app.php?r=corporate.contract&id=' . $result['id']);
        exit;
    }

    public static function contract(): void
    {
        Auth::requirePermission('corporate.report.view');
        $contract = Corporate::findContract((int) ($_GET['id'] ?? 0));
        if (!$contract) {
            http_response_code(404);
            echo 'Contract not found.';
            return;
        }
        self::assertCentreScope($contract);

        $main = View::render('corporate/contract', [
            'contract'     => $contract,
            'participants' => Corporate::participants((int) $contract['id']),
            'invoice'      => Corporate::contractInvoice((int) $contract['id']),
            'canManage'    => Auth::can('corporate.participant.manage'),
            'canApprove'   => Auth::can('corporate.contract.approve'),
            'inviteBase'   => rtrim((string) config('app.url'), '/') . '/corporate-invite.php?t=',
        ]);
        View::shell('corporate', $contract['reference'], $main);
    }

    public static function contractStatus(): void
    {
        Auth::requirePermission('corporate.contract.approve');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $error = Corporate::setContractStatus($id, (string) $_POST['status']);
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Updated.' : $error);
        header('Location: app.php?r=corporate.contract&id=' . $id);
        exit;
    }

    // --------------------------------------------------------------- participants

    public static function participantStore(): void
    {
        Auth::requirePermission('corporate.participant.manage');
        Csrf::requireValid();
        $contractId = (int) $_POST['contract_id'];
        self::assertCentreScope(Corporate::findContract($contractId) ?? []);

        $error = Corporate::addParticipant($contractId, [
            'name'      => trim((string) $_POST['name']),
            'email'     => trim((string) $_POST['email']),
            'phone'     => trim((string) ($_POST['phone'] ?? '')),
            'job_title' => trim((string) ($_POST['job_title'] ?? '')),
        ]);
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Participant nominated.' : $error);
        header('Location: app.php?r=corporate.contract&id=' . $contractId);
        exit;
    }

    public static function participantInvite(): void
    {
        Auth::requirePermission('corporate.participant.manage');
        Csrf::requireValid();
        $participantId = (int) $_POST['participant_id'];
        $contract = self::contractForParticipant($participantId);
        $error = Corporate::invite($participantId);
        Session::flash($error === '' ? 'success' : 'error',
            $error === '' ? 'Invitation link generated — copy it from the list and send it on.' : $error);
        header('Location: app.php?r=corporate.contract&id=' . ($contract['id'] ?? (int) $_POST['contract_id']));
        exit;
    }

    public static function participantWithdraw(): void
    {
        Auth::requirePermission('corporate.participant.manage');
        Csrf::requireValid();
        $participantId = (int) $_POST['participant_id'];
        $contract = self::contractForParticipant($participantId);
        Corporate::withdrawParticipant($participantId);
        Session::flash('success', 'Participant withdrawn — their seat is free again.');
        header('Location: app.php?r=corporate.contract&id=' . ($contract['id'] ?? (int) $_POST['contract_id']));
        exit;
    }

    /**
     * Resolves a participant to its contract and enforces the same centre scope
     * participantStore()/contract() already apply — a participant_id posted on its own
     * must not let a scoped viewer reach a contract outside their centre.
     */
    private static function contractForParticipant(int $participantId): array
    {
        $participant = Corporate::findParticipant($participantId);
        if (!$participant) {
            http_response_code(404);
            echo 'Participant not found.';
            exit;
        }
        $contract = Corporate::findContract((int) $participant['contract_id']);
        self::assertCentreScope($contract ?? []);
        return $contract ?? [];
    }

    // --------------------------------------------------------------------- report

    public static function report(): void
    {
        Auth::requirePermission('corporate.report.view');
        $contract = Corporate::findContract((int) ($_GET['id'] ?? 0));
        if (!$contract) {
            http_response_code(404);
            echo 'Contract not found.';
            return;
        }
        self::assertCentreScope($contract);

        $rows = Corporate::report((int) $contract['id']);

        if (($_GET['format'] ?? '') === 'csv') {
            Audit::log('corporate.report_exported', 'contracts', (int) $contract['id'], null, ['rows' => count($rows)]);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $contract['reference'] . '-report.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Job title', 'Status', 'Student no', 'Sessions marked', 'Attendance %', 'Average assessment %', 'Certificates']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['name'], $r['email'], $r['job_title'], $r['status'], $r['student_no'],
                    (int) $r['sessions_marked'], $r['attendance_rate'] ?? 'n/a',
                    $r['avg_assessment'] ?? 'n/a', (int) $r['certificates'],
                ]);
            }
            fclose($out);
            exit;
        }

        $main = View::render('corporate/report', ['contract' => $contract, 'rows' => $rows]);
        View::shell('corporate', 'Report — ' . $contract['reference'], $main);
    }

    // -------------------------------------------------------------------- helpers

    /**
     * A centre-scoped viewer may only open contracts delivered at their centre.
     * Online contracts (centre_id NULL) are global and stay with unscoped viewers,
     * consistent with Decision 8.
     */
    private static function assertCentreScope(array $contract): void
    {
        $scope = Auth::scopeCentres('corporate.report.view');
        if ($scope === null) {
            return;
        }
        $centreId = isset($contract['centre_id']) && $contract['centre_id'] !== null ? (int) $contract['centre_id'] : null;
        if ($centreId === null || !in_array($centreId, $scope, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
    }

    /** @return array<string,mixed> */
    private static function organisationForm(): array
    {
        $type = (string) ($_POST['type'] ?? 'company');
        $allowed = ['company', 'bank', 'government', 'parastatal', 'ngo', 'institution', 'other'];
        return [
            'name'            => trim((string) $_POST['name']),
            'type'            => in_array($type, $allowed, true) ? $type : 'company',
            'registration_no' => trim((string) ($_POST['registration_no'] ?? '')),
            'industry'        => trim((string) ($_POST['industry'] ?? '')),
            'address_line'    => trim((string) ($_POST['address_line'] ?? '')),
            'city'            => trim((string) ($_POST['city'] ?? '')),
            'state'           => trim((string) ($_POST['state'] ?? '')),
            'website'         => trim((string) ($_POST['website'] ?? '')),
            'notes'           => trim((string) ($_POST['notes'] ?? '')),
            'status'          => in_array((string) ($_POST['status'] ?? 'prospect'), ['prospect', 'active', 'dormant', 'blocked'], true)
                                 ? (string) $_POST['status'] : 'prospect',
        ];
    }

    /** @return array<string,mixed> */
    private static function requestForm(): array
    {
        $mode = (string) ($_POST['delivery_mode'] ?? 'unspecified');
        return [
            'organisation_id'   => (int) ($_POST['organisation_id'] ?? 0) ?: null,
            'organisation_name' => trim((string) $_POST['organisation_name']),
            'contact_name'      => trim((string) $_POST['contact_name']),
            'contact_email'     => trim((string) $_POST['contact_email']),
            'contact_phone'     => trim((string) ($_POST['contact_phone'] ?? '')),
            'programme_id'      => (int) ($_POST['programme_id'] ?? 0) ?: null,
            'participants'      => (int) ($_POST['participants'] ?? 0) ?: null,
            'preferred_start'   => $_POST['preferred_start'] ?: null,
            'delivery_mode'     => in_array($mode, ['physical', 'online', 'hybrid', 'unspecified'], true) ? $mode : 'unspecified',
            'centre_id'         => (int) ($_POST['centre_id'] ?? 0) ?: null,
            'requirements'      => trim((string) ($_POST['requirements'] ?? '')),
        ];
    }
}
