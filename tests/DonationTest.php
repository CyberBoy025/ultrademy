<?php
declare(strict_types=1);

/**
 * Donation rules that can be checked without a database — the campaign window, the
 * progress arithmetic, and the amount guards that run before anything is written.
 */

require_once dirname(__DIR__) . '/app/core/Money.php';
require_once dirname(__DIR__) . '/app/models/Donation.php';

$campaign = static fn(array $over = []): array => array_merge([
    'status' => 'published', 'starts_on' => null, 'ends_on' => null,
    'target_amount' => null, 'raised_amount' => 0, 'currency' => 'NGN',
], $over);

// ------------------------------------------------------------- campaign window

test('a draft or closed campaign never accepts gifts', function () use ($campaign) {
    assertFalse_(Donation::campaignIsOpen($campaign(['status' => 'draft'])));
    assertFalse_(Donation::campaignIsOpen($campaign(['status' => 'closed'])));
});

test('a published campaign with no dates is open', function () use ($campaign) {
    assertTrue_(Donation::campaignIsOpen($campaign()));
});

test('a campaign is shut before it opens and after it closes', function () use ($campaign) {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    assertFalse_(Donation::campaignIsOpen($campaign(['starts_on' => $tomorrow])));
    assertFalse_(Donation::campaignIsOpen($campaign(['ends_on' => $yesterday])));
    assertTrue_(Donation::campaignIsOpen($campaign(['starts_on' => $yesterday, 'ends_on' => $tomorrow])));
});

test('a campaign closing today is still open on its closing day', function () use ($campaign) {
    // Off-by-one here would shut an appeal a day early, on the day people are most
    // likely to give.
    assertTrue_(Donation::campaignIsOpen($campaign(['ends_on' => date('Y-m-d')])));
    assertTrue_(Donation::campaignIsOpen($campaign(['starts_on' => date('Y-m-d')])));
});

// -------------------------------------------------------------------- progress

test('progress is null when there is no target, so no bar is drawn', function () use ($campaign) {
    assertSame_(null, Donation::progressPercent($campaign(['raised_amount' => 500000])));
    assertSame_(null, Donation::progressPercent($campaign(['target_amount' => 0, 'raised_amount' => 500000])));
});

test('progress is the honest percentage of the target', function () use ($campaign) {
    assertSame_(50, Donation::progressPercent($campaign(['target_amount' => 1000000, 'raised_amount' => 500000])));
    assertSame_(0, Donation::progressPercent($campaign(['target_amount' => 1000000, 'raised_amount' => 0])));
});

test('progress caps at 100 so an over-funded appeal does not overflow its bar', function () use ($campaign) {
    assertSame_(100, Donation::progressPercent($campaign(['target_amount' => 1000000, 'raised_amount' => 5000000])));
});

// -------------------------------------------------------------- amount guards

test('a donation below the floor is refused before anything is written', function () {
    // These branches return before touching the database, which is what makes them
    // testable here — and what makes them a real guard rather than a UI hint.
    $r = Donation::start(null, ['email' => 'a@b.com', 'name' => 'A'], 500);
    assertFalse_($r['ok']);
    assertTrue_(str_contains((string) $r['error'], 'smallest'));
    assertSame_(null, $r['donation']);
});

test('an implausibly large donation is refused rather than charged', function () {
    // Catches the fat-fingered extra zero, and a card tester probing with a big number.
    $r = Donation::start(null, ['email' => 'a@b.com', 'name' => 'A'], 900000000);
    assertFalse_($r['ok']);
    assertSame_(null, $r['donation']);
});

test('a zero or negative amount is refused', function () {
    assertFalse_(Donation::start(null, ['email' => 'a@b.com', 'name' => 'A'], 0)['ok']);
    assertFalse_(Donation::start(null, ['email' => 'a@b.com', 'name' => 'A'], -5000)['ok']);
});

// ------------------------------------------------------------------ references

test('donation references are unique, prefixed and not sequential', function () {
    $seen = [];
    for ($i = 0; $i < 200; $i++) {
        $ref = Donation::newReference();
        assertTrue_(str_starts_with($ref, 'ULD-'), 'donations use their own prefix, distinct from ULP- payments');
        assertFalse_(isset($seen[$ref]), 'reference collision');
        $seen[$ref] = true;
    }
});

test('the suggested amounts are sane and ascending', function () {
    $prev = 0;
    foreach (Donation::PRESETS as $p) {
        assertTrue_($p > $prev, 'presets ascend');
        assertTrue_($p >= 10000, 'no preset sits below the minimum the code will accept');
        $prev = $p;
    }
});
