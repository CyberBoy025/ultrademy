<?php
declare(strict_types=1);

/** Invoices, the verification queue, expenses, refunds, reports (README §26–§31). */
final class FinanceController
{
    // ------------------------------------------------------------------- invoices

    public static function invoices(): void
    {
        Auth::requirePermission('finance.invoice.view_any');
        $scope = Auth::scopeCentres('finance.invoice.view_any');
        $status = (string) ($_GET['status'] ?? '');

        $main = View::render('finance/invoices', [
            'invoices' => Invoice::listing($scope, $status ?: null),
            'status' => $status,
            'canCreate' => Auth::can('finance.invoice.create'),
            'users' => Auth::can('finance.invoice.create') ? User::allWithRoles(Auth::scopeCentres('identity.user.view_any')) : [],
            'centres' => Centre::all($scope),
        ]);
        View::shell('invoices', 'Invoices', $main);
    }

    public static function storeInvoice(): void
    {
        Auth::requirePermission('finance.invoice.create');
        Csrf::requireValid();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $amount = Money::toMinor((string) ($_POST['amount'] ?? '0'));

        if (!User::find($userId) || $description === '' || $amount <= 0) {
            Session::flash('error', 'Choose a user, a description and an amount greater than zero.');
            header('Location: app.php?r=invoices');
            exit;
        }

        $invoiceId = Invoice::issue(
            $userId,
            [['description' => $description, 'quantity' => 1, 'unit_amount' => $amount]],
            in_array($_POST['payable_type'] ?? '', ['enrolment', 'subscription', 'application_fee', 'other'], true) ? $_POST['payable_type'] : 'other',
            ($_POST['payable_id'] ?? '') !== '' ? (int) $_POST['payable_id'] : null,
            ($_POST['centre_id'] ?? '') !== '' ? (int) $_POST['centre_id'] : null,
            ($_POST['due_on'] ?? '') !== '' ? $_POST['due_on'] : null
        );

        $invoice = Invoice::find($invoiceId);
        Audit::log('invoice.issued', 'invoices', $invoiceId, null,
            ['number' => $invoice['number'], 'total' => $amount],
            $invoice['centre_id'] !== null ? (int) $invoice['centre_id'] : null);
        Session::flash('success', "Invoice {$invoice['number']} issued.");
        header('Location: app.php?r=invoices.show&id=' . $invoiceId);
        exit;
    }

    public static function showInvoice(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $invoice = Invoice::find($id);
        if (!$invoice) {
            http_response_code(404);
            echo 'Invoice not found.';
            return;
        }
        $isOwner = (int) $invoice['user_id'] === (int) Auth::id();
        if (!$isOwner) {
            Auth::requirePermission('finance.invoice.view_any');
            self::assertCentreScope($invoice['centre_id'], 'finance.invoice.view_any');
        }

        $main = View::render('finance/invoice-show', [
            'invoice' => $invoice,
            'lines' => Invoice::linesFor($id),
            'payments' => Payment::forInvoice($id),
            'balance' => Invoice::balance($id),
            'isOwner' => $isOwner,
            'canRecordCash' => Auth::can('finance.payment.record'),
            'canVoid' => Auth::can('finance.invoice.void'),
            'canRefund' => Auth::can('finance.refund.create'),
            'methods' => PaymentService::availableForPayer(),
            'bank' => self::bankDetails(),
        ]);
        View::shell($isOwner && !Auth::can('finance.invoice.view_any') ? 'billing' : 'invoices', $invoice['number'], $main);
    }

    public static function voidInvoice(): void
    {
        Auth::requirePermission('finance.invoice.void');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '') {
            Session::flash('error', 'A void needs a reason — it is an audited action.');
            header('Location: app.php?r=invoices.show&id=' . $id);
            exit;
        }
        $error = Invoice::void($id, $reason);
        if ($error !== '') {
            Session::flash('error', $error);
        } else {
            Audit::log('invoice.voided', 'invoices', $id, null, ['reason' => $reason]);
            Session::flash('success', 'Invoice voided.');
        }
        header('Location: app.php?r=invoices.show&id=' . $id);
        exit;
    }

    // ------------------------------------------------------- payments & verification

    public static function payments(): void
    {
        Auth::requirePermission('finance.invoice.view_any');
        $scope = Auth::scopeCentres('finance.invoice.view_any');
        $main = View::render('finance/payments', [
            'payments' => Payment::listing($scope, (string) ($_GET['status'] ?? '') ?: null, (string) ($_GET['method'] ?? '') ?: null),
            'status' => (string) ($_GET['status'] ?? ''),
            'method' => (string) ($_GET['method'] ?? ''),
        ]);
        View::shell('payments', 'Payments', $main);
    }

    public static function verificationQueue(): void
    {
        Auth::requirePermission('finance.payment.verify');
        $scope = Auth::scopeCentres('finance.payment.verify');
        $main = View::render('finance/verify-queue', [
            'payments' => Payment::pendingVerification($scope),
        ]);
        View::shell('verify', 'Verify Transfers', $main);
    }

    public static function showPayment(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $payment = Payment::find($id);
        if (!$payment) {
            http_response_code(404);
            echo 'Payment not found.';
            return;
        }
        $isOwner = (int) $payment['user_id'] === (int) Auth::id();
        if (!$isOwner) {
            Auth::requirePermission('finance.invoice.view_any');
        }

        $main = View::render('finance/payment-show', [
            'payment' => $payment,
            'proofs' => Payment::proofsFor($id),
            'canVerify' => Auth::can('finance.payment.verify'),
            'isOwnPayment' => $isOwner,
            'duplicate' => $payment['bank_reference'] ? Payment::duplicateBankReference($payment['bank_reference'], $id) : null,
            'canRefund' => Auth::can('finance.refund.create'),
        ]);
        View::shell('payments', $payment['reference'], $main);
    }

    public static function verifyPayment(): void
    {
        Auth::requirePermission('finance.payment.verify');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $approve = ($_POST['decision'] ?? '') === 'approve';

        $error = PaymentService::verifyManual($id, $approve, trim((string) ($_POST['note'] ?? '')));
        if ($error !== '') {
            Session::flash('error', $error);
        } else {
            Session::flash('success', $approve ? 'Payment verified and receipted.' : 'Payment rejected.');
        }
        header('Location: app.php?r=verify');
        exit;
    }

    public static function downloadProof(): void
    {
        $proof = Payment::findProof((int) ($_GET['id'] ?? 0));
        if (!$proof) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }
        $payment = Payment::find((int) $proof['payment_id']);
        $isOwner = $payment && (int) $payment['user_id'] === (int) Auth::id();
        if (!$isOwner && !Auth::can('finance.invoice.view_any')) {
            http_response_code(403);
            echo 'Not permitted.';
            return;
        }
        Upload::stream(Payment::SUBDIR, $proof['stored_name'], $proof['mime_type'], $proof['original_name']);
    }

    // -------------------------------------------------------------------- cashier

    public static function recordCash(): void
    {
        Auth::requirePermission('finance.payment.record');
        Csrf::requireValid();
        $invoiceId = (int) $_POST['invoice_id'];
        $amount = Money::toMinor((string) ($_POST['amount'] ?? '0'));

        // A cashier's own centre, so till takings land against the right hub (§31).
        $scope = Auth::scopeCentres('finance.payment.record');
        $centreId = ($scope !== null && count($scope) === 1) ? $scope[0] : null;

        $result = PaymentService::recordCash($invoiceId, $amount, $centreId);
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Cash recorded and receipt issued.' : $result['error']);
        header('Location: app.php?r=invoices.show&id=' . $invoiceId);
        exit;
    }

    // -------------------------------------------------------------------- expenses

    public static function expenses(): void
    {
        Auth::requirePermission('finance.expense.record');
        $scope = Auth::scopeCentres('finance.expense.record');
        $main = View::render('finance/expenses', [
            'expenses' => Expense::listing($scope, (string) ($_GET['status'] ?? '') ?: null),
            'status' => (string) ($_GET['status'] ?? ''),
            'centres' => Centre::all($scope),
            'canApprove' => Auth::can('finance.expense.approve'),
        ]);
        View::shell('expenses', 'Expenses', $main);
    }

    public static function storeExpense(): void
    {
        Auth::requirePermission('finance.expense.record');
        Csrf::requireValid();
        $amount = Money::toMinor((string) ($_POST['amount'] ?? '0'));
        $category = trim((string) ($_POST['category'] ?? ''));
        if ($amount <= 0 || $category === '') {
            Session::flash('error', 'A category and an amount above zero are required.');
            header('Location: app.php?r=expenses');
            exit;
        }
        $id = Expense::create(
            ($_POST['centre_id'] ?? '') !== '' ? (int) $_POST['centre_id'] : null,
            $category,
            $amount,
            trim((string) ($_POST['description'] ?? '')),
            ($_POST['incurred_on'] ?? '') !== '' ? $_POST['incurred_on'] : date('Y-m-d')
        );
        Audit::log('expense.recorded', 'expenses', $id, null, ['amount' => $amount, 'category' => $category]);
        Session::flash('success', 'Expense recorded and submitted for approval.');
        header('Location: app.php?r=expenses');
        exit;
    }

    public static function decideExpense(): void
    {
        Auth::requirePermission('finance.expense.approve');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $status = ($_POST['decision'] ?? '') === 'approve' ? 'approved' : 'rejected';
        Expense::decide($id, $status);
        Audit::log('expense.' . $status, 'expenses', $id, null, ['status' => $status]);
        Session::flash('success', 'Expense ' . $status . '.');
        header('Location: app.php?r=expenses');
        exit;
    }

    // --------------------------------------------------------------------- refunds

    public static function refunds(): void
    {
        if (!Auth::can('finance.refund.create') && !Auth::can('finance.refund.approve')) {
            Auth::requirePermission('finance.refund.create');
        }
        $main = View::render('finance/refunds', [
            'refunds' => Refund::listing((string) ($_GET['status'] ?? '') ?: null),
            'status' => (string) ($_GET['status'] ?? ''),
            'canApprove' => Auth::can('finance.refund.approve'),
        ]);
        View::shell('refunds', 'Refunds', $main);
    }

    public static function storeRefund(): void
    {
        Auth::requirePermission('finance.refund.create');
        Csrf::requireValid();
        $result = Refund::request(
            (int) $_POST['payment_id'],
            Money::toMinor((string) ($_POST['amount'] ?? '0')),
            trim((string) ($_POST['reason'] ?? '')) ?: 'No reason given'
        );
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Audit::log('refund.requested', 'refunds', (int) $result['id'], null, ['payment_id' => (int) $_POST['payment_id']]);
            Session::flash('success', 'Refund raised — it needs management approval.');
        }
        header('Location: app.php?r=refunds');
        exit;
    }

    public static function decideRefund(): void
    {
        Auth::requirePermission('finance.refund.approve');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $approve = ($_POST['decision'] ?? '') === 'approve';
        $error = Refund::decide($id, $approve, trim((string) ($_POST['note'] ?? '')));
        if ($error !== '') {
            Session::flash('error', $error);
        } else {
            Audit::log('refund.' . ($approve ? 'approved' : 'rejected'), 'refunds', $id);
            Session::flash('success', 'Refund ' . ($approve ? 'approved' : 'rejected') . '.');
        }
        header('Location: app.php?r=refunds');
        exit;
    }

    // --------------------------------------------------------------------- reports

    public static function reports(): void
    {
        Auth::requirePermission('finance.report.view');
        $scope = Auth::scopeCentres('finance.report.view');
        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to   = (string) ($_GET['to'] ?? date('Y-m-d'));

        $main = View::render('finance/reports', [
            'from' => $from,
            'to' => $to,
            'summary' => FinanceReport::summary($scope, $from, $to),
            'byCentre' => $scope === null ? FinanceReport::byCentre($from, $to) : [],
            'byMethod' => FinanceReport::revenueByMethod($from, $to),
            'outstanding' => FinanceReport::outstandingInvoices($scope),
            'isGlobal' => $scope === null,
            'canReconcile' => Auth::can('finance.reconciliation.run'),
            'runs' => Auth::can('finance.reconciliation.run')
                ? Database::all('SELECT * FROM reconciliation_runs ORDER BY created_at DESC LIMIT 5') : [],
        ]);
        View::shell('reports', 'Financial Reports', $main);
    }

    public static function reconcile(): void
    {
        Auth::requirePermission('finance.reconciliation.run');
        Csrf::requireValid();
        $from = (string) ($_POST['from'] ?? date('Y-m-01'));
        $to   = (string) ($_POST['to'] ?? date('Y-m-d'));

        $result = Reconciliation::run($from, $to, Auth::id());
        Audit::log('reconciliation.run', 'reconciliation_runs', $result['id'], null, [
            'checked' => $result['checked'], 'exceptions' => $result['exceptions'],
        ]);
        Session::flash('success', sprintf(
            'Reconciliation complete — %d checked, %d matched, %d exception(s) for review.',
            $result['checked'], $result['matched'], $result['exceptions']
        ));
        header('Location: app.php?r=reports');
        exit;
    }

    // -------------------------------------------------------------- payer-facing

    /** A learner's own invoices and receipts (README §44 student "Payments"). */
    public static function billing(): void
    {
        $userId = (int) Auth::id();
        $main = View::render('finance/billing', [
            'invoices' => Invoice::forUser($userId),
            'payments' => Payment::forUser($userId),
        ]);
        View::shell('billing', 'My Payments', $main);
    }

    public static function payInvoice(): void
    {
        Csrf::requireValid();
        $invoiceId = (int) $_POST['invoice_id'];
        $invoice = Invoice::find($invoiceId);
        if (!$invoice || (int) $invoice['user_id'] !== (int) Auth::id()) {
            http_response_code(403);
            echo 'Not your invoice.';
            return;
        }
        $method = (string) ($_POST['method'] ?? '');

        if ($method === 'bank_transfer') {
            $result = PaymentService::submitBankTransfer(
                $invoiceId,
                (int) Auth::id(),
                trim((string) ($_POST['bank_reference'] ?? '')),
                $_FILES['proof'] ?? []
            );
            if (!$result['ok']) {
                Session::flash('error', $result['error']);
            } else {
                Session::flash('success', 'Transfer submitted. Finance will verify it — nothing is credited until they do.'
                    . ($result['warning'] ? ' ' . $result['warning'] : ''));
            }
            header('Location: app.php?r=invoices.show&id=' . $invoiceId);
            exit;
        }

        $result = PaymentService::initialise($invoiceId, $method, (int) Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            header('Location: app.php?r=invoices.show&id=' . $invoiceId);
            exit;
        }
        header('Location: ' . $result['url']);
        exit;
    }

    // -------------------------------------------------------------------- helpers

    /** Decision 19: bank details are a global setting, not hard-coded and not per centre. */
    public static function bankDetails(): array
    {
        return [
            'bank' => (string) (Setting::get('bank_name', '') ?? ''),
            'account_name' => (string) (Setting::get('bank_account_name', '') ?? ''),
            'account_number' => (string) (Setting::get('bank_account_number', '') ?? ''),
        ];
    }

    private static function assertCentreScope(?string $centreId, string $permission): void
    {
        $scope = Auth::scopeCentres($permission);
        if ($scope === null) {
            return;
        }
        $id = $centreId !== null ? (int) $centreId : null;
        if ($id === null || !in_array($id, $scope, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
    }
}
