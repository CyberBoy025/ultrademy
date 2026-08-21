<?php
declare(strict_types=1);

/**
 * Corporate pricing arithmetic. Small surface, but it is the number on a document sent to
 * a bank, so it is worth pinning.
 */

require_once dirname(__DIR__) . '/app/core/Money.php';
require_once dirname(__DIR__) . '/app/models/Corporate.php';

test('a proposal total is seats times unit price', function () {
    // 30 seats at ₦75,000 = ₦2,250,000
    assertSame_(225_000_000, Corporate::proposalTotal(30, 7_500_000, 0));
    assertSame_(7_500_000, Corporate::proposalTotal(1, 7_500_000, 0));
});

test('a discount comes off the total, not the seat price', function () {
    assertSame_(215_000_000, Corporate::proposalTotal(30, 7_500_000, 10_000_000));
});

test('a total can never go negative', function () {
    // A discount larger than the value must floor at zero rather than produce a credit
    // note by accident. The controller rejects it too; this is the second line.
    assertSame_(0, Corporate::proposalTotal(2, 1000, 999_999));
});

test('a free-of-charge proposal is valid, not an error', function () {
    // Pilot cohorts and goodwill training are real. Zero is a price.
    assertSame_(0, Corporate::proposalTotal(10, 0, 0));
});

test('pricing is exact at scale — no float drift', function () {
    // Seats × price in integer kobo. The reason none of this is a float.
    assertSame_(999_999_999, Corporate::proposalTotal(999, 1_001_001, 0));
});
