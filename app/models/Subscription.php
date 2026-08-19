<?php
declare(strict_types=1);

/**
 * Subscription lifecycle, per 04-subscriptions-entitlements.md §7.
 *
 * One reading decision worth recording: the doc's lifecycle DIAGRAM shows
 * ACTIVE --user cancels--> CANCELLED, but the PROSE immediately below it says
 * cancelling "leaves the subscription active until ends_at ... The customer paid for
 * the period." Those disagree. This implementation follows the prose, because the
 * prose states a business rule and the diagram is shorthand:
 *
 *   cancel()  → status stays 'active', cancelled_at set, auto_renew off. Access continues.
 *   expire()  → at ends_at, becomes 'cancelled' if it was cancelled, else 'expired'.
 *
 * So the terminal status still records WHY it ended, without cutting off access that
 * was already paid for.
 */
final class Subscription
{
    public static function activeFor(int $userId): ?array
    {
        return Database::one(
            "SELECT s.*, p.name AS package_name, p.code AS package_code, p.price_amount, p.currency, p.billing_period
             FROM subscriptions s JOIN packages p ON p.id = s.package_id
             WHERE s.user_id = :u AND s.status = 'active'",
            ['u' => $userId]
        );
    }

    public static function pendingFor(int $userId): ?array
    {
        return Database::one(
            "SELECT s.*, p.name AS package_name FROM subscriptions s JOIN packages p ON p.id = s.package_id
             WHERE s.user_id = :u AND s.status = 'pending' ORDER BY s.created_at DESC LIMIT 1",
            ['u' => $userId]
        );
    }

    public static function historyFor(int $userId): array
    {
        return Database::all(
            'SELECT s.*, p.name AS package_name FROM subscriptions s JOIN packages p ON p.id = s.package_id
             WHERE s.user_id = :u ORDER BY s.created_at DESC',
            ['u' => $userId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            "SELECT s.*, p.name AS package_name, p.duration_days,
                    CONCAT(pr.first_name,' ',pr.last_name) AS user_name, u.email
             FROM subscriptions s
             JOIN packages p ON p.id = s.package_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN user_profiles pr ON pr.user_id = u.id
             WHERE s.id = :id",
            ['id' => $id]
        );
    }

    public static function all(?string $status = null): array
    {
        $sql = "SELECT s.*, p.name AS package_name, u.email,
                       CONCAT(pr.first_name,' ',pr.last_name) AS user_name
                FROM subscriptions s
                JOIN packages p ON p.id = s.package_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN user_profiles pr ON pr.user_id = u.id";
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' WHERE s.status = :st';
            $params['st'] = $status;
        }
        $sql .= ' ORDER BY s.created_at DESC';
        return Database::all($sql, $params);
    }

    /** Creates a PENDING subscription. It grants nothing until activated (§7). */
    public static function request(int $userId, int $packageId): int
    {
        Database::query(
            "INSERT INTO subscriptions (user_id, package_id, status) VALUES (:u,:p,'pending')",
            ['u' => $userId, 'p' => $packageId]
        );
        return Database::lastInsertId();
    }

    /**
     * Activates a pending subscription, superseding whatever the user held before.
     *
     * Decision 13 (no proration): the new period starts now at full duration; no credit
     * is carried over from the superseded subscription.
     * Decision 14 (one active per user) is enforced by a unique index on a generated
     * column, so the supersede step is required, not merely tidy.
     */
    public static function activate(int $id): void
    {
        $sub = self::find($id);
        if (!$sub) {
            throw new RuntimeException("Subscription $id not found");
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::query(
                "UPDATE subscriptions SET status = 'expired', ends_at = NOW()
                 WHERE user_id = :u AND status = 'active'",
                ['u' => $sub['user_id']]
            );
            Database::query(
                "UPDATE subscriptions
                 SET status = 'active', starts_at = NOW(),
                     ends_at = DATE_ADD(NOW(), INTERVAL :days DAY), activated_by = :by
                 WHERE id = :id",
                ['days' => (int) $sub['duration_days'], 'by' => Auth::id(), 'id' => $id]
            );
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        Entitlements::flush((int) $sub['user_id']);
    }

    /** Renewal extends the existing period rather than starting a new row. */
    public static function renew(int $id): void
    {
        $sub = self::find($id);
        Database::query(
            'UPDATE subscriptions SET ends_at = DATE_ADD(GREATEST(ends_at, NOW()), INTERVAL :days DAY) WHERE id = :id',
            ['days' => (int) $sub['duration_days'], 'id' => $id]
        );
        Entitlements::flush((int) $sub['user_id']);
    }

    /** Access continues to ends_at — see the class docblock. */
    public static function cancel(int $id): void
    {
        Database::query(
            'UPDATE subscriptions SET cancelled_at = NOW(), auto_renew = 0 WHERE id = :id',
            ['id' => $id]
        );
    }

    public static function void(int $id): void
    {
        Database::query("UPDATE subscriptions SET status = 'void' WHERE id = :id AND status = 'pending'", ['id' => $id]);
    }

    /**
     * Batch expiry. Decision 12: no grace period — a hard stop at ends_at.
     * Returns the rows that were expired, so the caller can fire notifications (§37).
     */
    public static function expireDue(): array
    {
        $due = Database::all(
            "SELECT id, user_id, cancelled_at FROM subscriptions
             WHERE status = 'active' AND ends_at IS NOT NULL AND ends_at < NOW()"
        );
        foreach ($due as $row) {
            Database::query(
                "UPDATE subscriptions SET status = :st WHERE id = :id",
                ['st' => $row['cancelled_at'] !== null ? 'cancelled' : 'expired', 'id' => $row['id']]
            );
            Entitlements::flush((int) $row['user_id']);
        }
        return $due;
    }
}
