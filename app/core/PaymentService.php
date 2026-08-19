<?php
declare(strict_types=1);

/**
 * Everything that moves money goes through here.
 *
 * The rules this class exists to enforce (05-finance-payments.md §1, §5, §6) — each one
 * is here rather than in a controller because a controller is easy to bypass and easy to
 * forget when the next entry point is added:
 *
 *   1. The browser callback NEVER marks a payment successful. Only a signed webhook or an
 *      explicit server-side verify() does. A student can visit the success URL without
 *      paying; that must change nothing.
 *   2. The submitter can never be the verifier.
 *   3. A webhook is recorded before it is acted on, so a retry cannot credit twice.
 *   4. Amount and currency are re-checked against our own invoice before crediting.
 *   5. Ledger rows are immutable — corrections are new rows, never edits.
 */
final class PaymentService
{
    /** @return array<string,PaymentGatewayInterface> */
    public static function gateways(): array
    {
        return [
            'paystack'      => new PaystackGateway(),
            'flutterwave'   => new FlutterwaveGateway(),
            'bank_transfer' => new ManualGateway('bank_transfer'),
            'cash'          => new ManualGateway('cash'),
        ];
    }

    public static function gateway(string $method): PaymentGatewayInterface
    {
        $gateways = self::gateways();
        if (!isset($gateways[$method])) {
            throw new InvalidArgumentException("Unknown payment method: $method");
        }
        return $gateways[$method];
    }

    /** Methods a payer may actually choose right now (configured providers + bank transfer). */
    public static function availableForPayer(): array
    {
        $out = [];
        foreach (self::gateways() as $code => $gw) {
            if ($code === 'cash') {
                continue; // cash is recorded by a cashier, never chosen online
            }
            if ($gw->isConfigured()) {
                $out[$code] = $gw->label();
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ online flow

    /**
     * Starts an online payment. Creates the payment row as `initiated` — which grants
     * nothing — and hands back a redirect URL.
     *
     * @return array{ok:bool,url:?string,error:?string,payment_id:?int}
     */
    public static function initialise(int $invoiceId, string $method, int $userId): array
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return ['ok' => false, 'url' => null, 'error' => 'Invoice not found.', 'payment_id' => null];
        }
        if (in_array($invoice['status'], ['paid', 'void'], true)) {
            return ['ok' => false, 'url' => null, 'error' => 'This invoice is already settled or void.', 'payment_id' => null];
        }
        $balance = Invoice::balance($invoiceId);
        if ($balance <= 0) {
            return ['ok' => false, 'url' => null, 'error' => 'Nothing left to pay on this invoice.', 'payment_id' => null];
        }

        $gateway = self::gateway($method);
        if (!$gateway->isConfigured()) {
            return ['ok' => false, 'url' => null, 'error' => $gateway->label() . ' is not configured.', 'payment_id' => null];
        }

        $reference = Payment::newReference();
        $payer = User::find($userId) ?? [];

        Database::query(
            "INSERT INTO payments (reference, invoice_id, user_id, method, amount, currency, status, centre_id)
             VALUES (:ref,:inv,:u,:m,:amt,:cur,'initiated',:centre)",
            [
                'ref' => $reference, 'inv' => $invoiceId, 'u' => $userId, 'm' => $method,
                'amt' => $balance, 'cur' => $invoice['currency'],
                'centre' => $invoice['centre_id'],
            ]
        );
        $paymentId = Database::lastInsertId();

        $result = $gateway->initialise($invoice, $payer, $reference, $balance, $invoice['currency']);
        if (($result['error'] ?? null) !== null) {
            Database::query(
                "UPDATE payments SET status = 'failed', failure_reason = :r WHERE id = :id",
                ['r' => mb_substr((string) $result['error'], 0, 255), 'id' => $paymentId]
            );
            return ['ok' => false, 'url' => null, 'error' => $result['error'], 'payment_id' => $paymentId];
        }

        if (($result['gateway_reference'] ?? null) !== null) {
            Database::query('UPDATE payments SET gateway_reference = :g WHERE id = :id', [
                'g' => $result['gateway_reference'], 'id' => $paymentId,
            ]);
        }
        Audit::log('payment.initiated', 'payments', $paymentId, null, ['method' => $method, 'amount' => $balance],
            $invoice['centre_id'] !== null ? (int) $invoice['centre_id'] : null);

        return ['ok' => true, 'url' => $result['authorisation_url'], 'error' => null, 'payment_id' => $paymentId];
    }

    /**
     * Server-side confirmation. This is what the "return from gateway" page calls — it
     * asks the GATEWAY, it does not trust the browser.
     */
    public static function verifyWithGateway(int $paymentId): string
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return 'Payment not found.';
        }
        if ($payment['status'] === 'successful') {
            return '';
        }
        if ($payment['gateway_reference'] === null) {
            return 'This payment has no gateway reference to check.';
        }

        $result = self::gateway($payment['method'])->verify($payment['gateway_reference']);
        if ($result['status'] === 'successful') {
            $error = self::assertAmountMatches($payment, $result['amount_minor'], $result['currency']);
            if ($error !== '') {
                return $error;
            }
            self::markSuccessful((int) $payment['id'], null, 'gateway_verify');
            return '';
        }
        if ($result['status'] === 'failed') {
            self::markFailed((int) $payment['id'], 'Gateway reported failure.');
            return 'The gateway reports this payment failed.';
        }
        return 'Still pending at the gateway.';
    }

    // ---------------------------------------------------------------- manual flow

    /**
     * A payer declares a bank transfer they have already made. This creates a payment in
     * `pending_verification` — it credits nothing until a human with
     * finance.payment.verify approves it.
     *
     * @return array{ok:bool,error:?string,warning:?string,payment_id:?int}
     */
    public static function submitBankTransfer(int $invoiceId, int $userId, string $bankReference, array $proofFile): array
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return ['ok' => false, 'error' => 'Invoice not found.', 'warning' => null, 'payment_id' => null];
        }
        if (in_array($invoice['status'], ['paid', 'void'], true)) {
            return ['ok' => false, 'error' => 'This invoice is already settled or void.', 'warning' => null, 'payment_id' => null];
        }
        if (trim($bankReference) === '') {
            return ['ok' => false, 'error' => 'Enter the bank reference from your transfer.', 'warning' => null, 'payment_id' => null];
        }

        $balance = Invoice::balance($invoiceId);
        $duplicate = Payment::duplicateBankReference(trim($bankReference));

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $reference = Payment::newReference();
            Database::query(
                "INSERT INTO payments (reference, invoice_id, user_id, method, bank_reference, amount, currency, status, centre_id)
                 VALUES (:ref,:inv,:u,'bank_transfer',:bank,:amt,:cur,'pending_verification',:centre)",
                [
                    'ref' => $reference, 'inv' => $invoiceId, 'u' => $userId,
                    'bank' => trim($bankReference), 'amt' => $balance, 'cur' => $invoice['currency'],
                    'centre' => $invoice['centre_id'],
                ]
            );
            $paymentId = Database::lastInsertId();

            if (($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $stored = Upload::store($proofFile, Payment::SUBDIR, Upload::DOCUMENT_TYPES, 5 * 1024 * 1024);
                if (is_string($stored)) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => $stored, 'warning' => null, 'payment_id' => null];
                }
                Database::query(
                    'INSERT INTO payment_proofs (payment_id, stored_name, original_name, mime_type, size_bytes, uploaded_by)
                     VALUES (:p,:s,:o,:m,:z,:by)',
                    [
                        'p' => $paymentId, 's' => $stored['stored_name'], 'o' => $stored['original_name'],
                        'm' => $stored['mime_type'], 'z' => $stored['size_bytes'], 'by' => $userId,
                    ]
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Audit::log('payment.transfer_submitted', 'payments', $paymentId, null,
            ['bank_reference' => trim($bankReference), 'amount' => $balance],
            $invoice['centre_id'] !== null ? (int) $invoice['centre_id'] : null);

        return [
            'ok' => true,
            'error' => null,
            'warning' => $duplicate ? 'That bank reference has been submitted before — finance will check it carefully.' : null,
            'payment_id' => $paymentId,
        ];
    }

    /**
     * Approve or reject a declared transfer.
     *
     * §6: "The submitter can never be the verifier. Enforced in PaymentService, not the
     * UI." A UI that merely hides the button is not a control.
     */
    public static function verifyManual(int $paymentId, bool $approve, string $note): string
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return 'Payment not found.';
        }
        if ($payment['status'] !== 'pending_verification') {
            return 'Only a payment awaiting verification can be decided.';
        }
        if ((int) $payment['user_id'] === (int) Auth::id()) {
            return 'You cannot verify your own payment.';
        }

        if ($approve) {
            self::markSuccessful($paymentId, (int) Auth::id(), 'manual_verify');
            Audit::log('payment.verified', 'payments', $paymentId,
                ['status' => 'pending_verification'], ['status' => 'successful', 'note' => $note],
                $payment['centre_id'] !== null ? (int) $payment['centre_id'] : null);
            Notify::send((int) $payment['user_id'], 'payment.verified', 'payment',
                'Payment confirmed',
                'We have confirmed your transfer of ' . Money::format((int) $payment['amount'], $payment['currency']) . '.',
                'app.php?r=payments.show&id=' . $paymentId);
        } else {
            Database::query(
                "UPDATE payments SET status = 'failed', failure_reason = :r, verified_by = :by, verified_at = NOW()
                 WHERE id = :id",
                ['r' => mb_substr($note !== '' ? $note : 'Rejected by finance.', 0, 255), 'by' => Auth::id(), 'id' => $paymentId]
            );
            Audit::log('payment.rejected', 'payments', $paymentId,
                ['status' => 'pending_verification'], ['status' => 'failed', 'reason' => $note],
                $payment['centre_id'] !== null ? (int) $payment['centre_id'] : null);
            Notify::send((int) $payment['user_id'], 'payment.rejected', 'payment',
                'We could not confirm your payment',
                $note !== '' ? $note : 'Finance could not match your transfer. Please check the reference and contact us.',
                'app.php?r=payments.show&id=' . $paymentId);
        }
        return '';
    }

    /**
     * Cash taken at the desk. The money is already in hand, so it is successful on
     * record — the control here is that `recorded_by` is captured and the till is
     * reconciled daily, not a second approval (§8: a cashier may record and receipt).
     */
    public static function recordCash(int $invoiceId, int $amountMinor, ?int $centreId): array
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return ['ok' => false, 'error' => 'Invoice not found.', 'payment_id' => null];
        }
        if (in_array($invoice['status'], ['paid', 'void'], true)) {
            return ['ok' => false, 'error' => 'This invoice is already settled or void.', 'payment_id' => null];
        }
        if ($amountMinor <= 0) {
            return ['ok' => false, 'error' => 'Enter an amount greater than zero.', 'payment_id' => null];
        }
        if ($amountMinor > Invoice::balance($invoiceId)) {
            return ['ok' => false, 'error' => 'That is more than the outstanding balance.', 'payment_id' => null];
        }

        Database::query(
            "INSERT INTO payments (reference, invoice_id, user_id, method, amount, currency, status, paid_at, recorded_by, centre_id)
             VALUES (:ref,:inv,:u,'cash',:amt,:cur,'successful',NOW(),:by,:centre)",
            [
                'ref' => Payment::newReference(), 'inv' => $invoiceId, 'u' => $invoice['user_id'],
                'amt' => $amountMinor, 'cur' => $invoice['currency'], 'by' => Auth::id(),
                'centre' => $centreId ?? $invoice['centre_id'],
            ]
        );
        $paymentId = Database::lastInsertId();

        self::afterSuccessful($paymentId, (int) $invoiceId, Auth::id());
        Audit::log('payment.cash_recorded', 'payments', $paymentId, null,
            ['amount' => $amountMinor], $centreId);

        return ['ok' => true, 'error' => null, 'payment_id' => $paymentId];
    }

    // ------------------------------------------------------------------- webhooks

    /**
     * @param array<string,string> $headers lower-cased
     * @return array{http:int,message:string}
     */
    public static function handleWebhook(string $provider, string $rawBody, array $headers, ?string $remoteIp): array
    {
        $gateways = self::gateways();
        if (!isset($gateways[$provider])) {
            return ['http' => 404, 'message' => 'Unknown provider.'];
        }
        $gateway = $gateways[$provider];

        $signatureValid = $gateway->verifySignature($rawBody, $headers);
        $parsed = $gateway->parseWebhook($rawBody);
        $eventId = $parsed['event_id'] ?? null;

        if ($eventId === null) {
            return ['http' => 400, 'message' => 'No event id.'];
        }

        // Record BEFORE acting, so a retried delivery is a no-op rather than a double
        // credit. Deduplication applies ONLY to validly signed events — see migration 051:
        // if unsigned requests could claim the unique slot, anyone could block a real
        // payment by guessing an event id.
        try {
            Database::query(
                'INSERT INTO webhook_events (provider, event_id, event_type, payload, signature_valid, remote_ip)
                 VALUES (:p,:e,:t,:pl,:sv,:ip)',
                [
                    'p' => $provider, 'e' => $eventId, 't' => $parsed['event_type'],
                    'pl' => mb_substr($rawBody, 0, 60000), 'sv' => $signatureValid ? 1 : 0, 'ip' => $remoteIp,
                ]
            );
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return ['http' => 200, 'message' => 'Duplicate event ignored.'];
            }
            throw $e;
        }
        $eventRowId = Database::lastInsertId();

        // A bad signature is stored (it is a security signal) but never processed.
        if (!$signatureValid) {
            Database::query("UPDATE webhook_events SET error = 'Invalid signature' WHERE id = :id", ['id' => $eventRowId]);
            Audit::log('webhook.signature_invalid', 'webhook_events', $eventRowId, null, [
                'provider' => $provider, 'remote_ip' => $remoteIp,
            ]);
            return ['http' => 401, 'message' => 'Invalid signature.'];
        }

        $gatewayRef = $parsed['gateway_reference'] ?? null;
        $payment = $gatewayRef !== null ? Payment::findByGatewayReference($gatewayRef) : null;
        if (!$payment && $gatewayRef !== null) {
            $payment = Payment::findByReference($gatewayRef);
        }
        if (!$payment) {
            self::failEvent($eventRowId, 'No matching payment for ' . (string) $gatewayRef);
            return ['http' => 200, 'message' => 'No matching payment.'];
        }

        if ($payment['status'] === 'successful') {
            Database::query('UPDATE webhook_events SET processed_at = NOW() WHERE id = :id', ['id' => $eventRowId]);
            return ['http' => 200, 'message' => 'Already applied.'];
        }

        if (($parsed['status'] ?? '') !== 'successful') {
            self::markFailed((int) $payment['id'], 'Gateway reported failure.');
            Database::query('UPDATE webhook_events SET processed_at = NOW() WHERE id = :id', ['id' => $eventRowId]);
            return ['http' => 200, 'message' => 'Recorded as failed.'];
        }

        $mismatch = self::assertAmountMatches($payment, $parsed['amount_minor'] ?? null, $parsed['currency'] ?? null);
        if ($mismatch !== '') {
            self::failEvent($eventRowId, $mismatch);
            Audit::log('webhook.amount_mismatch', 'payments', (int) $payment['id'], null, ['detail' => $mismatch]);
            return ['http' => 200, 'message' => 'Amount mismatch — held for review.'];
        }

        self::markSuccessful((int) $payment['id'], null, 'webhook');
        Database::query('UPDATE webhook_events SET processed_at = NOW() WHERE id = :id', ['id' => $eventRowId]);
        return ['http' => 200, 'message' => 'Applied.'];
    }

    // -------------------------------------------------------------------- internals

    /** Re-check what the gateway claims against what we billed, before crediting anything. */
    private static function assertAmountMatches(array $payment, ?int $amountMinor, ?string $currency): string
    {
        if ($amountMinor !== null && $amountMinor !== (int) $payment['amount']) {
            return sprintf('Amount mismatch: gateway says %d, we expected %d.', $amountMinor, (int) $payment['amount']);
        }
        if ($currency !== null && strtoupper($currency) !== strtoupper((string) $payment['currency'])) {
            return sprintf('Currency mismatch: gateway says %s, we expected %s.', $currency, $payment['currency']);
        }
        return '';
    }

    private static function markFailed(int $paymentId, string $reason): void
    {
        Database::query(
            "UPDATE payments SET status = 'failed', failure_reason = :r WHERE id = :id AND status <> 'successful'",
            ['r' => mb_substr($reason, 0, 255), 'id' => $paymentId]
        );
    }

    private static function markSuccessful(int $paymentId, ?int $verifiedBy, string $source): void
    {
        $payment = Payment::find($paymentId);
        if (!$payment || $payment['status'] === 'successful') {
            return;
        }
        Database::query(
            "UPDATE payments SET status = 'successful', paid_at = NOW(), verified_by = :by,
                    verified_at = CASE WHEN :by2 IS NULL THEN verified_at ELSE NOW() END
             WHERE id = :id",
            ['by' => $verifiedBy, 'by2' => $verifiedBy, 'id' => $paymentId]
        );
        self::afterSuccessful($paymentId, (int) $payment['invoice_id'], $verifiedBy);
        Audit::log('payment.succeeded', 'payments', $paymentId, ['status' => $payment['status']],
            ['status' => 'successful', 'source' => $source],
            $payment['centre_id'] !== null ? (int) $payment['centre_id'] : null);
    }

    /** Receipt, invoice status, and the downstream effects the money pays for. */
    private static function afterSuccessful(int $paymentId, int $invoiceId, ?int $issuedBy): void
    {
        self::issueReceipt($paymentId, $issuedBy);
        $status = Invoice::refreshStatus($invoiceId);

        if ($status === 'paid') {
            self::fulfil($invoiceId);

            $invoice = Invoice::find($invoiceId);
            if ($invoice) {
                Notify::send((int) $invoice['user_id'], 'invoice.paid', 'payment',
                    'Invoice ' . $invoice['number'] . ' is paid',
                    'Thank you — ' . Money::format((int) $invoice['total_amount'], $invoice['currency']) . ' received in full.',
                    'app.php?r=invoices.show&id=' . $invoiceId);
            }
        }
    }

    /** Decision 20: receipts are sequentially numbered. One per payment, enforced by a unique key. */
    private static function issueReceipt(int $paymentId, ?int $issuedBy): void
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return;
        }
        if (Database::one('SELECT 1 FROM receipts WHERE payment_id = :p', ['p' => $paymentId])) {
            return;
        }
        $pdo = Database::pdo();
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $number = DocumentNumber::next('RCP');
            Database::query(
                'INSERT INTO receipts (number, payment_id, user_id, amount, currency, issued_by)
                 VALUES (:n,:p,:u,:a,:c,:by)',
                [
                    'n' => $number, 'p' => $paymentId, 'u' => $payment['user_id'],
                    'a' => $payment['amount'], 'c' => $payment['currency'], 'by' => $issuedBy,
                ]
            );
            if ($ownTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * What the money actually buys. These are the two manual bridges Phases 6 and 7 left
     * open, now closed: a paid subscription invoice activates the subscription, and a paid
     * enrolment invoice moves the enrolment out of `pending_payment`.
     */
    private static function fulfil(int $invoiceId): void
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice || $invoice['payable_id'] === null) {
            return;
        }
        $payableId = (int) $invoice['payable_id'];

        if ($invoice['payable_type'] === 'subscription') {
            $sub = Database::one('SELECT id, status FROM subscriptions WHERE id = :id', ['id' => $payableId]);
            if ($sub && $sub['status'] === 'pending') {
                Subscription::activate($payableId);
                Audit::log('subscription.activated', 'subscriptions', $payableId,
                    ['status' => 'pending'], ['status' => 'active', 'source' => 'invoice_paid']);
            }
        } elseif ($invoice['payable_type'] === 'enrolment') {
            $enr = Database::one('SELECT id, status, centre_id FROM enrolments WHERE id = :id', ['id' => $payableId]);
            if ($enr && $enr['status'] === 'pending_payment') {
                Enrolment::setStatus($payableId, 'active');
                Audit::log('enrolment.activated', 'enrolments', $payableId,
                    ['status' => 'pending_payment'], ['status' => 'active', 'source' => 'invoice_paid'],
                    $enr['centre_id'] !== null ? (int) $enr['centre_id'] : null);
            }
        }
    }

    private static function failEvent(int $eventRowId, string $error): void
    {
        Database::query('UPDATE webhook_events SET error = :e WHERE id = :id', [
            'e' => mb_substr($error, 0, 255), 'id' => $eventRowId,
        ]);
    }
}
