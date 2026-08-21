<?php
declare(strict_types=1);

/**
 * Tiny migration runner: applies database/migrations/*.sql in filename order,
 * once each, tracked in a `migrations` table. Run from CLI:
 *   php database/migrate.php
 *
 * The actual work lives in Migrator::run() (app/core/Migrator.php), shared with the
 * Utilities page's "Run Migration" action — this script is just the CLI-facing wrapper.
 */

require __DIR__ . '/../config/bootstrap.php';

try {
    $result = Migrator::run();
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

foreach ($result['files'] as $name) {
    echo "Applying $name ... done\n";
}
echo $result['ran'] === 0 ? "Nothing to migrate — up to date.\n" : "{$result['ran']} migration(s) applied.\n";
