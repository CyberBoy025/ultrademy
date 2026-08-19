<?php
declare(strict_types=1);

final class Payment
{
    public const SUBDIR = 'proofs';

    private const BASE_SELECT = "SELECT p.*,
            i.number AS invoice_number, i.total_amount AS invoice_total, i.payable_type, i.payable_id,
            u.email,
            CONCAT(pr.first_name,' ',pr.last_name) AS user_name,
            c.name AS centre_name,
            CONCAT(vp.first_name,' ',vp.last_name) AS verifier_name,
            r.number AS receipt_number
        FROM payments p
        JOIN invoices i ON i.id = p.invoice_id
        JOIN users u ON u.id = p.user_id
        LEFT JOIN user_profiles pr ON pr.user_id = u.id
        LEFT JOIN centres c ON c.id = p.centre_id
        LEFT JOIN user_profiles vp ON vp.user_id = p.verified_by
        LEFT JOIN receipts r ON r.payment_id = p.id";

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE p.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function findByReference(string $reference): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE p.reference = ?', [$reference])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function findByGatewayReference(string $ref): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE p.gateway_reference = ?', [$ref])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function forInvoice(int $invoiceId): array
    {
        return Database::query(self::BASE_SELECT . ' WHERE p.invoice_id = ? ORDER BY p.created_at', [$invoiceId])->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        return Database::query(self::BASE_SELECT . ' WHERE p.user_id = ? ORDER BY p.created_at DESC', [$userId])->fetchAll();
    }

    /** @param array<int,int>|null $centreIds */
    public static function listing(?array $centreIds, ?string $status = null, ?string $method = null): array
    {
        $where = [];
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $where[] = "p.centre_id IN ($ph)";
            array_push($params, ...array_values($centreIds));
        }
        if ($status !== null && $status !== '') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($method !== null && $method !== '') {
            $where[] = 'p.method = ?';
            $params[] = $method;
        }
        $sql = self::BASE_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY p.created_at DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    /** The verification queue — bank transfers waiting on a human. */
    public static function pendingVerification(?array $centreIds): array
    {
        $sql = self::BASE_SELECT . " WHERE p.status = 'pending_verification'";
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " AND p.centre_id IN ($ph)";
            $params = array_values($centreIds);
        }
        return Database::query($sql . ' ORDER BY p.created_at', $params)->fetchAll();
    }

    public static function proofsFor(int $paymentId): array
    {
        return Database::all('SELECT * FROM payment_proofs WHERE payment_id = :p ORDER BY id', ['p' => $paymentId]);
    }

    public static function findProof(int $id): ?array
    {
        return Database::one('SELECT * FROM payment_proofs WHERE id = :id', ['id' => $id]);
    }

    /** §6: warn if this bank reference has been submitted before — a duplicate-claim signal. */
    public static function duplicateBankReference(string $bankReference, ?int $excludePaymentId = null): ?array
    {
        $sql = 'SELECT id, reference, status, user_id FROM payments WHERE bank_reference = :b';
        $params = ['b' => $bankReference];
        if ($excludePaymentId !== null) {
            $sql .= ' AND id <> :x';
            $params['x'] = $excludePaymentId;
        }
        return Database::one($sql . ' LIMIT 1', $params);
    }

    public static function newReference(): string
    {
        return 'ULP-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}
