<?php
declare(strict_types=1);

final class Package
{
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM packages';
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= ' ORDER BY sort_order, price_amount';
        return Database::all($sql);
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM packages WHERE id = :id', ['id' => $id]);
    }

    /** @return array<int,int|null> feature_id => limit_value (null = unlimited) */
    public static function featureMap(int $packageId): array
    {
        $rows = Database::all('SELECT feature_id, limit_value FROM package_features WHERE package_id = :p', ['p' => $packageId]);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['feature_id']] = $r['limit_value'] === null ? null : (int) $r['limit_value'];
        }
        return $out;
    }

    /** Full feature rows a package grants, for display. */
    public static function featuresFor(int $packageId): array
    {
        return Database::all(
            'SELECT f.*, pf.limit_value FROM package_features pf
             JOIN features f ON f.id = pf.feature_id
             WHERE pf.package_id = :p ORDER BY f.module, f.name',
            ['p' => $packageId]
        );
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO packages (code, name, description, price_amount, currency, billing_period, duration_days, status, sort_order)
             VALUES (:code,:name,:description,:price_amount,:currency,:billing_period,:duration_days,:status,:sort_order)',
            $data
        );
        return Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::query(
            'UPDATE packages SET name=:name, description=:description, price_amount=:price_amount,
                billing_period=:billing_period, duration_days=:duration_days, status=:status, sort_order=:sort_order
             WHERE id=:id',
            $data
        );
    }

    /**
     * Replaces the whole feature matrix for a package in one transaction.
     * @param array<int,int|null> $features feature_id => limit_value (null = unlimited)
     */
    public static function setFeatures(int $packageId, array $features): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::query('DELETE FROM package_features WHERE package_id = :p', ['p' => $packageId]);
            foreach ($features as $featureId => $limit) {
                Database::query(
                    'INSERT INTO package_features (package_id, feature_id, limit_value) VALUES (:p,:f,:l)',
                    ['p' => $packageId, 'f' => (int) $featureId, 'l' => $limit]
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function subscriberCount(int $packageId): int
    {
        return (int) Database::one(
            "SELECT COUNT(*) c FROM subscriptions WHERE package_id = :p AND status = 'active'",
            ['p' => $packageId]
        )['c'];
    }
}
