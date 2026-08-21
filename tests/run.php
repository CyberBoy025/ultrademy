<?php
declare(strict_types=1);

/**
 * Minimal test runner. No Composer in this project, so no PHPUnit — this is a plain
 * assert harness that runs with `php tests/run.php` and exits non-zero on failure,
 * which is all a CI step needs.
 *
 * Tests that need a database live behind a DB_TEST_DSN environment variable and skip
 * themselves when it is absent, so the suite is always runnable on a fresh checkout.
 * See PermissionScopeTest.php for the one test file that currently uses it — it builds
 * its own throwaway schema from the real migration files, so it never touches whatever
 * database APP_URL's .env points at.
 */

$passed = 0;
$failed = 0;
$skipped = 0;
$failures = [];

function test(string $name, callable $fn): void
{
    global $passed, $failed, $skipped, $failures;
    try {
        $fn();
        $passed++;
        echo "  ok   $name\n";
    } catch (SkipTest $e) {
        $skipped++;
        echo "  skip $name — {$e->getMessage()}\n";
    } catch (Throwable $e) {
        $failed++;
        $failures[] = "$name: " . $e->getMessage();
        echo "  FAIL $name\n       " . $e->getMessage() . "\n";
    }
}

final class SkipTest extends RuntimeException {}

function skip(string $why): void
{
    throw new SkipTest($why);
}

/** @param mixed $expected @param mixed $actual */
function assertSame_(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($message !== '' ? $message . ' — ' : '') .
            'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

function assertTrue_(bool $value, string $message = 'expected true'): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}

function assertFalse_(bool $value, string $message = 'expected false'): void
{
    if ($value) {
        throw new RuntimeException($message);
    }
}

echo "UltrAdemy test suite\n";
echo str_repeat('=', 60), "\n";

foreach (glob(__DIR__ . '/*Test.php') as $file) {
    echo "\n" . basename($file, '.php') . "\n";
    require $file;
}

echo "\n" . str_repeat('=', 60) . "\n";
printf("%d passed, %d failed, %d skipped\n", $passed, $failed, $skipped);
exit($failed > 0 ? 1 : 0);
