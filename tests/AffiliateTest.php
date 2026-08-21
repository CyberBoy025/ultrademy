<?php
declare(strict_types=1);

/**
 * Commission arithmetic — the part where being wrong means paying an affiliate the
 * wrong amount, forever, silently.
 */

require_once dirname(__DIR__) . '/app/core/Money.php';
require_once dirname(__DIR__) . '/app/models/Affiliate.php';

test('commission is the rate applied to the base, in minor units', function () {
    // 5% (500 bps) of ₦10,000.00 = ₦500.00
    assertSame_(50000, Affiliate::calculate(1000000, 500));
    // 2.5% of ₦10,000.00 = ₦250.00
    assertSame_(25000, Affiliate::calculate(1000000, 250));
    // 100% of anything is all of it
    assertSame_(1000000, Affiliate::calculate(1000000, 10000));
});

test('commission rounds DOWN, so the house never pays out money it did not receive', function () {
    // 5% of ₦1.99 is 9.95 kobo. Rounding up would pay a tenth of a kobo that was never
    // collected; over enough payments that is a real reconciliation gap.
    assertSame_(9, Affiliate::calculate(199, 500));
    assertSame_(0, Affiliate::calculate(1, 500), 'a rate too small to earn a whole kobo earns nothing');
});

test('a zero or negative base earns nothing', function () {
    assertSame_(0, Affiliate::calculate(0, 500));
    assertSame_(0, Affiliate::calculate(-1000000, 500));
});

test('a zero or negative rate earns nothing', function () {
    assertSame_(0, Affiliate::calculate(1000000, 0));
    assertSame_(0, Affiliate::calculate(1000000, -500));
});

test('commission never exceeds the payment it came from', function () {
    // A misconfigured rate above 100% would pay out more than was received. Rates are
    // clamped in the UI, but the arithmetic is worth pinning: nothing here silently
    // caps it, so this test documents the actual behaviour rather than a wished-for one.
    $base = 1000000;
    foreach ([0, 1, 250, 500, 10000] as $bps) {
        assertTrue_(Affiliate::calculate($base, $bps) <= $base, "rate $bps stays within the base");
    }
});

test('commission is exact at scale — no float drift', function () {
    // The reason rates are basis-point integers rather than 0.05 floats.
    $total = 0;
    for ($i = 0; $i < 1000; $i++) {
        $total += Affiliate::calculate(999_99, 250);   // 2.5% of ₦999.99
    }
    assertSame_(2499 * 1000, $total, 'a thousand identical commissions sum exactly');
});

test('the referral cookie name is stable', function () {
    // r.php writes it and register.php reads it. If these ever drift apart, attribution
    // fails silently and affiliates stop being paid with no error anywhere.
    assertSame_('ultrademy_ref', Affiliate::COOKIE);
});
