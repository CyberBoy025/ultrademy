<?php
declare(strict_types=1);

/** §8: the accountant raises a refund; management approves it. Never the same person. */
final class Refund
{
    private const BASE_SELECT = "SELECT r.*, p.reference AS payment_reference, p.amount AS payment_amount,
            p.method, i.number AS invoice_number,
            CONCAT(up.first_name,' ',up.last_name) AS user_name,
            CONCAT(rq.first_name,' ',rq.last_name) AS requester_name,
            CONCAT(ap.first_name,' ',ap.last_name) AS approver_name
        FROM refunds r
        JOIN payments p ON p.id = r.payment_id
        JOIN invoices i ON i.id = p.invoice_id
        LEFT JOIN user_profiles up ON up.user_id = p.user_id
        LEFT JOIN user_profiles rq ON rq.user_id = r.requested_by
        LEFT JOIN user_profiles ap ON ap.user_id = r.approved_by";

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE r.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function listing(?string $status = null): array
    {
        $sql = self::BASE_SELECT;
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' WHERE r.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY r.created_at DESC', $params)->fetchAll();
    }

    /** @return array{ok:bool,error:?string,id:?int} */
    public static function request(int $paymentId, int $amountMinor, string $reason): array
    {
        $payment = Payment::find($paymentId);
        if (!$payment) {
            return ['ok' => false, 'error' => 'Payment not found.', 'id' => null];
        }
        if ($payment['status'] !== 'successful') {
            return ['ok' => false, 'error' => 'Only a successful payment can be refunded.', 'id' => null];
        }
        $alreadyRefunded = (int) Database::one(
            "SELECT COALESCE(SUM(amount),0) a FROM refunds WHERE payment_id = :p AND status IN ('requested','approved','processed')",
            ['p' => $paymentId]
        )['a'];
        if ($amountMinor <= 0 || $amountMinor + $alreadyRefunded > (int) $payment['amount']) {
            return ['ok' => false, 'error' => 'Refund amount exceeds what was paid.', 'id' => null];
        }

        Database::query(
            "INSERT INTO refunds (payment_id, amount, currency, reason, status, requested_by)
             VALUES (:p,:a,:c,:r,'requested',:by)",
            ['p' => $paymentId, 'a' => $amountMinor, 'c' => $payment['currency'], 'r' => $reason, 'by' => Auth::id()]
        );
        return ['ok' => true, 'error' => null, 'id' => Database::lastInsertId()];
    }

    public static function decide(int $id, bool $approve, string $note): string
    {
        $refund = self::find($id);
        if (!$refund) {
            return 'Refund not found.';
        }
        if ($refund['status'] !== 'requested') {
            return 'This refund has already been decided.';
        }
        // Same two-person rule as payment verification.
        if ((int) $refund['requested_by'] === (int) Auth::id()) {
            return 'You cannot approve a refund you raised yourself.';
        }

        Database::query(
            'UPDATE refunds SET status = :s, approved_by = :by, decided_at = NOW(), decision_note = :n WHERE id = :id',
            ['s' => $approve ? 'approved' : 'rejected', 'by' => Auth::id(), 'n' => $note, 'id' => $id]
        );

        if ($approve) {
            // §1: ledger rows are immutable. The payment is marked reversed rather than
            // edited, and the refund row is the correcting entry.
            Database::query("UPDATE payments SET status = 'reversed' WHERE id = :p", ['p' => $refund['payment_id']]);
            Invoice::refreshStatus((int) Database::one('SELECT invoice_id FROM payments WHERE id = :p', ['p' => $refund['payment_id']])['invoice_id']);

            // Decision 34 (19-affiliate.md §10): a refunded payment claws back any
            // commission it earned. No-op if the payment never earned one.
            Affiliate::clawback((int) $refund['payment_id'], 'Payment refunded: ' . ($note !== '' ? $note : 'no reason given'));
        }
        return '';
    }
}
