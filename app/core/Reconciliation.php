<?php
declare(strict_types=1);

/**
 * §10. Matches our payment records against the gateway and records exceptions for a
 * human. It NEVER auto-corrects — "auto-correcting a mismatch is how a bug quietly
 * rewrites the books."
 *
 * Two classes of check run here:
 *
 *   - Local-only checks, which work with no credentials at all: payments stuck in
 *     `initiated`, successful payments with no receipt, invoices whose status disagrees
 *     with the payments against them.
 *   - Gateway checks, which call verify() per payment and therefore need API keys. When a
 *     provider is unconfigured its payments are reported as `gateway_unavailable` rather
 *     than silently skipped, so an empty exception list never means "all good" by default.
 */
final class Reconciliation
{
    private const STUCK_MINUTES = 10;

    /** @return array{id:int,checked:int,matched:int,exceptions:int} */
    public static function run(string $from, string $to, ?int $runBy): array
    {
        $payments = Database::all(
            "SELECT p.*, i.number AS invoice_number FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             WHERE DATE(p.created_at) BETWEEN :f AND :t
             ORDER BY p.created_at",
            ['f' => $from, 't' => $to]
        );

        $exceptions = [];
        $matched = 0;

        foreach ($payments as $p) {
            $issue = self::inspect($p);
            if ($issue === null) {
                $matched++;
            } else {
                $exceptions[] = [
                    'payment_id' => (int) $p['id'],
                    'reference'  => $p['reference'],
                    'invoice'    => $p['invoice_number'],
                    'issue'      => $issue,
                ];
            }
        }

        // Invoices whose stored status disagrees with their payments — a signal that
        // something wrote a status directly instead of going through refreshStatus().
        $drifted = Database::all(
            "SELECT i.id, i.number, i.status, i.total_amount,
                    COALESCE(SUM(CASE WHEN p.status='successful' THEN p.amount END),0) AS paid
             FROM invoices i LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.status NOT IN ('void','draft') AND DATE(i.created_at) BETWEEN :f AND :t
             GROUP BY i.id
             HAVING (paid >= i.total_amount AND i.status <> 'paid')
                 OR (paid > 0 AND paid < i.total_amount AND i.status NOT IN ('part_paid','overdue'))",
            ['f' => $from, 't' => $to]
        );
        foreach ($drifted as $d) {
            $exceptions[] = [
                'payment_id' => null,
                'reference'  => $d['number'],
                'invoice'    => $d['number'],
                'issue'      => sprintf('Invoice status "%s" disagrees with payments totalling %s.', $d['status'], Money::format((int) $d['paid'])),
            ];
        }

        Database::query(
            'INSERT INTO reconciliation_runs (period_start, period_end, checked_count, matched_count, exception_count, exceptions, run_by)
             VALUES (:s,:e,:c,:m,:x,:j,:by)',
            [
                's' => $from, 'e' => $to, 'c' => count($payments), 'm' => $matched,
                'x' => count($exceptions), 'j' => json_encode($exceptions), 'by' => $runBy,
            ]
        );

        return [
            'id' => Database::lastInsertId(),
            'checked' => count($payments),
            'matched' => $matched,
            'exceptions' => count($exceptions),
        ];
    }

    /** @return string|null the problem, or null if the row looks right */
    private static function inspect(array $payment): ?string
    {
        $method = (string) $payment['method'];
        $status = (string) $payment['status'];

        if ($status === 'initiated') {
            $age = (time() - strtotime((string) $payment['created_at'])) / 60;
            if ($age > self::STUCK_MINUTES) {
                // §5: poll verify() for payments stuck in initiated for more than 10 minutes.
                if (in_array($method, ['paystack', 'flutterwave'], true) && $payment['gateway_reference'] !== null) {
                    $gateway = PaymentService::gateway($method);
                    if (!$gateway->isConfigured()) {
                        return 'Stuck in initiated for ' . (int) $age . ' min; ' . $method . ' is not configured so it cannot be checked (gateway_unavailable).';
                    }
                    $result = $gateway->verify((string) $payment['gateway_reference']);
                    if ($result['status'] === 'successful') {
                        return 'Gateway reports SUCCESS but our record says initiated — needs a human to apply it.';
                    }
                    if ($result['status'] === 'unknown') {
                        return 'Gateway could not be reached: ' . (string) ($result['error'] ?? 'unknown error');
                    }
                    return null; // genuinely still pending or failed at the gateway
                }
                return 'Stuck in initiated for ' . (int) $age . ' min with no gateway reference.';
            }
            return null;
        }

        if ($status === 'successful') {
            $receipt = Database::one('SELECT 1 FROM receipts WHERE payment_id = :p', ['p' => $payment['id']]);
            if (!$receipt) {
                return 'Successful payment with no receipt issued.';
            }
        }

        if ($status === 'pending_verification') {
            $age = (time() - strtotime((string) $payment['created_at'])) / 86400;
            if ($age > 3) {
                return 'Awaiting manual verification for ' . (int) $age . ' day(s).';
            }
        }

        return null;
    }
}
