<?php
declare(strict_types=1);

/**
 * Idempotent demo/dev seed. Safe to re-run — everything is INSERT IGNORE or
 * keyed on a unique column. NOT for production: passwords are a shared demo
 * password, printed below.
 *
 * Run: php database/seed.php
 *
 * The actual work lives in Seeder::run() (app/core/Seeder.php), shared with the
 * Utilities page's "Import Demo Database" action — this script is just the
 * CLI-facing wrapper.
 */

require __DIR__ . '/../config/bootstrap.php';

echo Seeder::run();
