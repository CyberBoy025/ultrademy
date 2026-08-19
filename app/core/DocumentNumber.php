<?php
declare(strict_types=1);

/**
 * Sequential document numbers for invoices and receipts.
 *
 * `SELECT COUNT(*) + 1` is the obvious implementation and it is wrong: two requests read
 * the same count and produce the same number.
 *
 * The first fix here was `INSERT IGNORE` followed by `SELECT ... FOR UPDATE`, which is
 * correct but deadlocks under real concurrency — the INSERT takes a shared gap lock and
 * the SELECT then tries to upgrade it to exclusive. A load test of six concurrent
 * processes lost 15 of 48 attempts to deadlock (it never produced a duplicate, but a
 * dropped invoice is not acceptable either).
 *
 * This version uses MySQL's atomic counter idiom instead: one statement claims the
 * number and advances the counter, with LAST_INSERT_ID() carrying the claimed value back
 * on the same connection. No second query, no lock upgrade, nothing to deadlock against.
 *
 * Gaps are still possible if a transaction rolls back after claiming a number — that is
 * fine and expected. 05-finance-payments.md §3: "Gaps are acceptable; duplicates are not."
 */
final class DocumentNumber
{
    public static function next(string $prefix): string
    {
        $period = date('ym');

        Database::query(
            'INSERT INTO number_sequences (prefix, period, next_value)
             VALUES (:p, :pe, LAST_INSERT_ID(1) + 1)
             ON DUPLICATE KEY UPDATE next_value = LAST_INSERT_ID(next_value) + 1',
            ['p' => $prefix, 'pe' => $period]
        );

        $value = (int) Database::pdo()->query('SELECT LAST_INSERT_ID()')->fetchColumn();

        return sprintf('%s-%s-%04d', $prefix, $period, $value);
    }
}
