<?php
declare(strict_types=1);

/**
 * Tiny migration runner: applies database/migrations/*.sql in filename order,
 * once each, tracked in a `migrations` table. Run from CLI:
 *   php database/migrate.php
 */

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';

$pdo = Database::pdo();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = array_column($pdo->query('SELECT filename FROM migrations')->fetchAll(), 'filename');

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files, SORT_STRING);

$ran = 0;
foreach ($files as $path) {
    $name = basename($path);
    if (in_array($name, $applied, true)) {
        continue;
    }
    $sql = file_get_contents($path);
    echo "Applying $name ... ";
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // MySQL DDL cannot be rolled back, so a failure here may have applied part of the
        // file. Report which one and stop, rather than dumping a stack trace and carrying
        // on into migrations that assume this one succeeded.
        echo "FAILED\n\n";
        fwrite(STDERR, "Migration $name failed:\n  " . $e->getMessage() . "\n\n"
            . "It was NOT recorded as applied. Any statements before the failure may have\n"
            . "run — check the schema before re-running.\n");
        exit(1);
    }
    $pdo->prepare('INSERT INTO migrations (filename) VALUES (:f)')->execute(['f' => $name]);
    echo "done\n";
    $ran++;
}

echo $ran === 0 ? "Nothing to migrate — up to date.\n" : "$ran migration(s) applied.\n";
