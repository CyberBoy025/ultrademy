<?php
declare(strict_types=1);

final class Invoice
{
    private const BASE_SELECT = "SELECT i.*,
            u.email,
            CONCAT(pr.first_name,' ',pr.last_name) AS user_name,
            c.name AS centre_name,
            (SELECT COALESCE(SUM(p.amount),0) FROM payments p
               WHERE p.invoice_id = i.id AND p.status = 'successful') AS paid_amount
        FROM invoices i
        JOIN users u ON u.id = i.user_id
        LEFT JOIN user_profiles pr ON pr.user_id = u.id
        LEFT JOIN centres c ON c.id = i.centre_id";

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE i.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function findByNumber(string $number): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE i.number = ?', [$number])->fetchAll();
        return $rows[0] ?? null;
    }

    public static function linesFor(int $invoiceId): array
    {
        return Database::all('SELECT * FROM invoice_lines WHERE invoice_id = :i ORDER BY sort_order, id', ['i' => $invoiceId]);
    }

    public static function forUser(int $userId): array
    {
        return Database::query(
            self::BASE_SELECT . " WHERE i.user_id = ? AND i.status <> 'draft' ORDER BY i.created_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Staff listing, centre-scoped.
     * @param array<int,int>|null $centreIds null = GLOBAL, [] = nothing visible
     */
    public static function listing(?array $centreIds, ?string $status = null): array
    {
        $where = [];
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            // §31: NULL centre means online/global and is NOT a scoped centre's business
            // (Decision 8 default).
            $where[] = "i.centre_id IN ($ph)";
            array_push($params, ...array_values($centreIds));
        }
        if ($status !== null && $status !== '') {
            $where[] = 'i.status = ?';
            $params[] = $status;
        }
        $sql = self::BASE_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY i.created_at DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Creates and issues an invoice with its lines, in one transaction so the number and
     * the rows cannot come apart.
     *
     * @param array<int,array{description:string,quantity:int,unit_amount:int}> $lines
     */
    public static function issue(
        int $userId,
        array $lines,
        string $payableType = 'other',
        ?int $payableId = null,
        ?int $centreId = null,
        ?string $dueOn = null,
        int $discountMinor = 0,
        string $currency = 'NGN'
    ): int {
        if (!$lines) {
            throw new InvalidArgumentException('An invoice needs at least one line.');
        }

        return Database::transaction(static function () use ($lines, $userId, $payableType, $payableId, $centreId, $dueOn, $discountMinor, $currency): int {
            $number = DocumentNumber::next('INV');

            $subtotal = 0;
            foreach ($lines as $l) {
                $subtotal += ((int) $l['quantity']) * ((int) $l['unit_amount']);
            }
            $total = max(0, $subtotal - $discountMinor);

            Database::query(
                "INSERT INTO invoices
                    (number, user_id, payable_type, payable_id, centre_id, subtotal_amount, discount_amount,
                     total_amount, currency, status, due_on, issued_at, created_by)
                 VALUES (:num,:u,:pt,:pid,:centre,:sub,:disc,:total,:cur,'issued',:due,NOW(),:by)",
                [
                    'num' => $number, 'u' => $userId, 'pt' => $payableType, 'pid' => $payableId,
                    'centre' => $centreId, 'sub' => $subtotal, 'disc' => $discountMinor,
                    'total' => $total, 'cur' => $currency, 'due' => $dueOn, 'by' => Auth::id(),
                ]
            );
            $invoiceId = Database::lastInsertId();

            foreach (array_values($lines) as $i => $l) {
                $lineAmount = ((int) $l['quantity']) * ((int) $l['unit_amount']);
                Database::query(
                    'INSERT INTO invoice_lines (invoice_id, description, quantity, unit_amount, line_amount, sort_order)
                     VALUES (:i,:d,:q,:u,:la,:s)',
                    [
                        'i' => $invoiceId, 'd' => $l['description'], 'q' => (int) $l['quantity'],
                        'u' => (int) $l['unit_amount'], 'la' => $lineAmount, 's' => $i,
                    ]
                );
            }
            return $invoiceId;
        });
    }

    public static function paidAmount(int $invoiceId): int
    {
        return (int) Database::one(
            "SELECT COALESCE(SUM(amount),0) a FROM payments WHERE invoice_id = :i AND status = 'successful'",
            ['i' => $invoiceId]
        )['a'];
    }

    public static function balance(int $invoiceId): int
    {
        $inv = Database::one('SELECT total_amount FROM invoices WHERE id = :i', ['i' => $invoiceId]);
        return max(0, ((int) $inv['total_amount']) - self::paidAmount($invoiceId));
    }

    /** Recomputes status from the payments that actually exist. Never called with a guess. */
    public static function refreshStatus(int $invoiceId): string
    {
        $inv = Database::one('SELECT total_amount, status, due_on FROM invoices WHERE id = :i', ['i' => $invoiceId]);
        if (!$inv || in_array($inv['status'], ['void', 'draft'], true)) {
            return $inv['status'] ?? 'draft';
        }
        $paid = self::paidAmount($invoiceId);
        $total = (int) $inv['total_amount'];

        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'part_paid';
        } elseif ($inv['due_on'] !== null && strtotime($inv['due_on']) < strtotime('today')) {
            $status = 'overdue';
        } else {
            $status = 'issued';
        }
        Database::query('UPDATE invoices SET status = :s WHERE id = :i', ['s' => $status, 'i' => $invoiceId]);
        return $status;
    }

    /** §3: a paid invoice cannot be voided — it must be refunded, which is a different permission. */
    public static function void(int $id, string $reason): string
    {
        $inv = self::find($id);
        if (!$inv) {
            return 'Invoice not found.';
        }
        if ((int) $inv['paid_amount'] > 0) {
            return 'This invoice has payments against it. Raise a refund instead of voiding it.';
        }
        Database::query(
            "UPDATE invoices SET status = 'void', void_reason = :r, voided_by = :by WHERE id = :i",
            ['r' => $reason, 'by' => Auth::id(), 'i' => $id]
        );
        return '';
    }

    /** Sweeps issued invoices whose due date has passed. Called by the finance cron. */
    public static function markOverdue(): int
    {
        $stmt = Database::query(
            "UPDATE invoices SET status = 'overdue'
             WHERE status IN ('issued','part_paid') AND due_on IS NOT NULL AND due_on < CURDATE()"
        );
        return $stmt->rowCount();
    }
}
