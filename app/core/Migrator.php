<?php
declare(strict_types=1);

/**
 * Applies database/migrations/*.sql in filename order, once each, tracked in a
 * `migrations` table. Shared by the CLI runner (database/migrate.php) and the
 * Utilities page's "Run Migration" action, so there is exactly one implementation.
 */
final class Migrator
{
    /** @return array{ran:int,files:array<int,string>} */
    public static function run(): array
    {
        $pdo = Database::pdo();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $applied = array_column($pdo->query('SELECT filename FROM migrations')->fetchAll(), 'filename');
        $files = glob(dirname(__DIR__, 2) . '/database/migrations/*.sql');
        sort($files, SORT_STRING);

        $ran = [];
        foreach ($files as $path) {
            $name = basename($path);
            if (in_array($name, $applied, true)) {
                continue;
            }
            $sql = file_get_contents($path);
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // MySQL DDL cannot be rolled back, so a failure here may have applied part of
                // the file. The migration is NOT recorded as applied — check the schema before
                // retrying.
                throw new RuntimeException(
                    "Migration $name failed: {$e->getMessage()} "
                    . "It was NOT recorded as applied; statements before the failure may have run.",
                    0,
                    $e
                );
            }
            $pdo->prepare('INSERT INTO migrations (filename) VALUES (:f)')->execute(['f' => $name]);
            $ran[] = $name;
        }

        return ['ran' => count($ran), 'files' => $ran];
    }

    /**
     * Drops every table in the configured database, then rebuilds the schema via run().
     * Destructive and irreversible — callers must gate this behind their own
     * permission/environment checks before calling it.
     *
     * @return array{dropped:int,ran:int,files:array<int,string>}
     */
    public static function reset(): array
    {
        $pdo = Database::pdo();
        $tables = $pdo->query(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchAll(PDO::FETCH_COLUMN);

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        return array_merge(['dropped' => count($tables)], self::run());
    }
}
