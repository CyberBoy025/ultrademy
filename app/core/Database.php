<?php
declare(strict_types=1);

/** Thin PDO singleton. No query builder — the schema is small enough that raw SQL stays readable. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                config('db.host'),
                config('db.port'),
                config('db.name'),
                config('db.charset')
            );
            self::$pdo = new PDO($dsn, config('db.user'), config('db.pass'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** @param array<string,mixed> $params */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @param array<string,mixed> $params @return array<int,array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** @param array<string,mixed> $params @return array<string,mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function lastInsertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Runs a callable inside a transaction, retrying on deadlock.
     *
     * InnoDB resolves a deadlock by killing one transaction; the correct response is to
     * retry it, not to surface a 500 to whoever was unlucky. Anything touching money
     * should go through here.
     *
     * @template T
     * @param callable():T $work
     * @return T
     */
    public static function transaction(callable $work, int $retries = 3): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $work(); // already inside one — let the outermost caller own it
        }

        for ($attempt = 1; ; $attempt++) {
            $pdo->beginTransaction();
            try {
                $result = $work();
                $pdo->commit();
                return $result;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // 1213 = deadlock, 1205 = lock wait timeout. Both are safe to retry.
                $code = (int) ($e->errorInfo[1] ?? 0);
                if (($code === 1213 || $code === 1205) && $attempt <= $retries) {
                    usleep(random_int(20_000, 120_000) * $attempt);
                    continue;
                }
                throw $e;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    }
}
