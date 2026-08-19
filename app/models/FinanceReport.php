<?php
declare(strict_types=1);

/**
 * Reporting queries (§31, README §16 "Gwagwalada vs Kubwa vs all centres").
 *
 * Online/global rows carry centre_id = NULL and are reported as their OWN line, never
 * folded into a physical centre — §31 is explicit that online transactions must not be
 * forced into an incorrect physical location.
 */
final class FinanceReport
{
    /** @param array<int,int>|null $centreIds */
    public static function summary(?array $centreIds, string $from, string $to): array
    {
        [$scopeSql, $scopeParams] = self::scope($centreIds, 'p.centre_id');

        $revenue = (int) Database::query(
            "SELECT COALESCE(SUM(p.amount),0) a FROM payments p
             WHERE p.status = 'successful' AND DATE(p.paid_at) BETWEEN ? AND ? $scopeSql",
            array_merge([$from, $to], $scopeParams)
        )->fetch()['a'];

        [$expScopeSql, $expScopeParams] = self::scope($centreIds, 'e.centre_id');
        $expenses = (int) Database::query(
            "SELECT COALESCE(SUM(e.amount),0) a FROM expenses e
             WHERE e.status = 'approved' AND e.incurred_on BETWEEN ? AND ? $expScopeSql",
            array_merge([$from, $to], $expScopeParams)
        )->fetch()['a'];

        // Outstanding is per-invoice (total minus what has been paid against THAT invoice)
        // and then summed. Subtracting two independent totals would be wrong the moment a
        // payment exists against an invoice outside the filtered set.
        [$invScopeSql, $invScopeParams] = self::scope($centreIds, 'i.centre_id');
        $outstanding = (int) Database::query(
            "SELECT COALESCE(SUM(GREATEST(i.total_amount - COALESCE(paid.amount,0), 0)),0) a
             FROM invoices i
             LEFT JOIN (
                 SELECT invoice_id, SUM(amount) AS amount FROM payments
                 WHERE status = 'successful' GROUP BY invoice_id
             ) paid ON paid.invoice_id = i.id
             WHERE i.status IN ('issued','part_paid','overdue') $invScopeSql",
            $invScopeParams
        )->fetch()['a'];

        $pendingVerification = (int) Database::query(
            "SELECT COUNT(*) c FROM payments p WHERE p.status = 'pending_verification' $scopeSql",
            $scopeParams
        )->fetch()['c'];

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => $revenue - $expenses,
            'outstanding' => max(0, $outstanding),
            'pending_verification' => $pendingVerification,
        ];
    }

    /** Revenue and expenses per centre, with online/global as its own row. */
    public static function byCentre(string $from, string $to): array
    {
        $rows = Database::all(
            "SELECT c.id, c.name,
                    COALESCE((SELECT SUM(p.amount) FROM payments p
                       WHERE p.status='successful' AND p.centre_id = c.id AND DATE(p.paid_at) BETWEEN :f AND :t),0) AS revenue,
                    COALESCE((SELECT SUM(e.amount) FROM expenses e
                       WHERE e.status='approved' AND e.centre_id = c.id AND e.incurred_on BETWEEN :f2 AND :t2),0) AS expenses
             FROM centres c ORDER BY c.name",
            ['f' => $from, 't' => $to, 'f2' => $from, 't2' => $to]
        );

        $onlineRevenue = (int) Database::one(
            "SELECT COALESCE(SUM(amount),0) a FROM payments
             WHERE status='successful' AND centre_id IS NULL AND DATE(paid_at) BETWEEN :f AND :t",
            ['f' => $from, 't' => $to]
        )['a'];
        $onlineExpenses = (int) Database::one(
            "SELECT COALESCE(SUM(amount),0) a FROM expenses
             WHERE status='approved' AND centre_id IS NULL AND incurred_on BETWEEN :f AND :t",
            ['f' => $from, 't' => $to]
        )['a'];

        $rows[] = [
            'id' => null,
            'name' => 'Online / Head office',
            'revenue' => $onlineRevenue,
            'expenses' => $onlineExpenses,
        ];
        return $rows;
    }

    public static function revenueByMethod(string $from, string $to): array
    {
        return Database::all(
            "SELECT method, COUNT(*) n, COALESCE(SUM(amount),0) total
             FROM payments WHERE status='successful' AND DATE(paid_at) BETWEEN :f AND :t
             GROUP BY method ORDER BY total DESC",
            ['f' => $from, 't' => $to]
        );
    }

    /** @param array<int,int>|null $centreIds */
    public static function outstandingInvoices(?array $centreIds): array
    {
        [$sql, $params] = self::scope($centreIds, 'i.centre_id');
        return Database::query(
            "SELECT i.id, i.number, i.total_amount, i.due_on, i.status, c.name AS centre_name,
                    CONCAT(pr.first_name,' ',pr.last_name) AS user_name,
                    COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id=i.id AND p.status='successful'),0) AS paid
             FROM invoices i
             LEFT JOIN centres c ON c.id = i.centre_id
             LEFT JOIN user_profiles pr ON pr.user_id = i.user_id
             WHERE i.status IN ('issued','part_paid','overdue') $sql
             ORDER BY i.due_on IS NULL, i.due_on",
            $params
        )->fetchAll();
    }

    /** @return array{0:string,1:array<int,int>} */
    private static function scope(?array $centreIds, string $column): array
    {
        if ($centreIds === null) {
            return ['', []];
        }
        if (empty($centreIds)) {
            return [' AND 1 = 0', []];
        }
        $ph = implode(',', array_fill(0, count($centreIds), '?'));
        return [" AND $column IN ($ph)", array_values($centreIds)];
    }
}
