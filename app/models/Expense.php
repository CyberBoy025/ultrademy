<?php
declare(strict_types=1);

final class Expense
{
    private const BASE_SELECT = "SELECT e.*, c.name AS centre_name,
            CONCAT(rp.first_name,' ',rp.last_name) AS recorder_name,
            CONCAT(ap.first_name,' ',ap.last_name) AS approver_name
        FROM expenses e
        LEFT JOIN centres c ON c.id = e.centre_id
        LEFT JOIN user_profiles rp ON rp.user_id = e.recorded_by
        LEFT JOIN user_profiles ap ON ap.user_id = e.approved_by";

    public static function find(int $id): ?array
    {
        $rows = Database::query(self::BASE_SELECT . ' WHERE e.id = ?', [$id])->fetchAll();
        return $rows[0] ?? null;
    }

    /** @param array<int,int>|null $centreIds */
    public static function listing(?array $centreIds, ?string $status = null): array
    {
        $where = [];
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $where[] = "e.centre_id IN ($ph)";
            array_push($params, ...array_values($centreIds));
        }
        if ($status !== null && $status !== '') {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }
        $sql = self::BASE_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY e.incurred_on DESC, e.id DESC';
        return Database::query($sql, $params)->fetchAll();
    }

    public static function create(?int $centreId, string $category, int $amountMinor, string $description, string $incurredOn): int
    {
        Database::query(
            "INSERT INTO expenses (centre_id, category, amount, currency, description, incurred_on, recorded_by, status)
             VALUES (:c,:cat,:a,'NGN',:d,:i,:by,'submitted')",
            [
                'c' => $centreId, 'cat' => $category, 'a' => $amountMinor,
                'd' => $description, 'i' => $incurredOn, 'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    /** §8: recording and approving an expense are different permissions. */
    public static function decide(int $id, string $status): void
    {
        Database::query(
            'UPDATE expenses SET status = :s, approved_by = :by, decided_at = NOW() WHERE id = :id',
            ['s' => $status, 'by' => Auth::id(), 'id' => $id]
        );
    }
}
