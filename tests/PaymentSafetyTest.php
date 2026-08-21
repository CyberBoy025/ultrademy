<?php
declare(strict_types=1);

/**
 * Money-path invariants — Phase 9.
 *
 * The payment code is written but its webhook path has never fired against a real
 * gateway (`webhook_events` is empty in the live database). These tests cover the parts
 * that can be proven without a gateway or a database: the arithmetic, the signature
 * comparison, and the unit conversion where a factor-of-100 slip would silently
 * over- or under-credit every Flutterwave payment.
 *
 * What they do NOT cover is the end-to-end flow. That needs a MySQL instance and gateway
 * test keys, and is the Phase 9 verification step still outstanding.
 */

// A stub for the settings store, declared BEFORE the gateways are loaded so their calls
// to Setting::get() resolve here rather than reaching for a database.
if (!class_exists('Setting')) {
    final class Setting
    {
        /** @var array<string,string> */
        public static array $store = [];

        public static function get(string $key, mixed $default = null): mixed
        {
            return self::$store[$key] ?? $default;
        }
    }
}

$core = dirname(__DIR__) . '/app/core/';
require_once $core . 'Money.php';
require_once $core . 'PaymentGatewayInterface.php';
require_once $core . 'PaystackGateway.php';
require_once $core . 'FlutterwaveGateway.php';

// ------------------------------------------------------------------------- money

test('money: major-unit input converts to minor units', function () {
    assertSame_(1500050, Money::toMinor('15,000.50'));
    assertSame_(1500000, Money::toMinor('15000'));
    assertSame_(50, Money::toMinor('0.50'));
});

test('money: currency symbols and stray text are ignored on input', function () {
    assertSame_(1500050, Money::toMinor('₦15,000.50'));
    assertSame_(1500050, Money::toMinor('NGN 15 000.50'));
});

test('money: empty or nonsense input is zero, not a crash', function () {
    assertSame_(0, Money::toMinor(''));
    assertSame_(0, Money::toMinor('abc'));
    assertSame_(0, Money::toMinor('-'));
});

test('money: fractional kobo rounds rather than vanishing', function () {
    // Truncation here would quietly lose money on every rounded amount.
    assertSame_(1, Money::toMinor('0.005'));
    assertSame_(0, Money::toMinor('0.004'));
});

test('money: a round trip through minor units and back is lossless', function () {
    foreach (['0.01', '9.99', '15000.50', '1234567.89'] as $major) {
        assertSame_($major, Money::toMajorString(Money::toMinor($major)), "round trip of $major");
    }
});

test('money: display formatting puts the symbol and separators in the right places', function () {
    assertSame_('₦15,000.50', Money::format(1500050));
    assertSame_('₦15,001', Money::formatShort(1500050));
    assertSame_('$9.99', Money::format(999, 'USD'));
});

test('money: an unknown currency falls back to its code rather than a wrong symbol', function () {
    assertSame_('KES 10.00', Money::format(1000, 'KES'));
});

// -------------------------------------------------------- paystack signature

test('paystack: a correctly signed body is accepted', function () {
    Setting::$store['paystack_secret_key'] = 'sk_test_deadbeef';
    $body = '{"event":"charge.success","data":{"id":123,"reference":"ULP-1","status":"success","amount":500000,"currency":"NGN"}}';
    $sig = hash_hmac('sha512', $body, 'sk_test_deadbeef');
    assertTrue_((new PaystackGateway())->verifySignature($body, ['x-paystack-signature' => $sig]));
});

test('paystack: a tampered body is rejected', function () {
    Setting::$store['paystack_secret_key'] = 'sk_test_deadbeef';
    $body = '{"amount":500000}';
    $sig = hash_hmac('sha512', $body, 'sk_test_deadbeef');
    // An attacker inflating the amount must not keep a valid signature.
    assertFalse_((new PaystackGateway())->verifySignature('{"amount":50000000}', ['x-paystack-signature' => $sig]));
});

test('paystack: a body signed with the wrong key is rejected', function () {
    Setting::$store['paystack_secret_key'] = 'sk_test_deadbeef';
    $body = '{"amount":500000}';
    assertFalse_((new PaystackGateway())->verifySignature($body, [
        'x-paystack-signature' => hash_hmac('sha512', $body, 'sk_test_attacker'),
    ]));
});

test('paystack: a missing signature header is rejected', function () {
    Setting::$store['paystack_secret_key'] = 'sk_test_deadbeef';
    assertFalse_((new PaystackGateway())->verifySignature('{}', []));
});

test('paystack: an unconfigured gateway rejects everything, it does not accept everything', function () {
    // The dangerous failure mode: no key configured, so the comparison degenerates and
    // every webhook is trusted. It must fail closed.
    Setting::$store['paystack_secret_key'] = '';
    assertFalse_((new PaystackGateway())->verifySignature('{}', ['x-paystack-signature' => '']));
    assertFalse_((new PaystackGateway())->verifySignature('{}', ['x-paystack-signature' => 'anything']));
});

// ----------------------------------------------------- flutterwave signature

test('flutterwave: the configured hash is accepted and anything else is not', function () {
    Setting::$store['flutterwave_webhook_hash'] = 'my-verif-hash';
    $gw = new FlutterwaveGateway();
    assertTrue_($gw->verifySignature('{}', ['verif-hash' => 'my-verif-hash']));
    assertFalse_($gw->verifySignature('{}', ['verif-hash' => 'wrong']));
    assertFalse_($gw->verifySignature('{}', []));
});

test('flutterwave: an unconfigured hash fails closed', function () {
    Setting::$store['flutterwave_webhook_hash'] = '';
    assertFalse_((new FlutterwaveGateway())->verifySignature('{}', ['verif-hash' => '']));
});

// ------------------------------------------------------------ unit conversion

test('paystack amounts pass through unconverted — it already speaks kobo', function () {
    $parsed = (new PaystackGateway())->parseWebhook(
        '{"event":"charge.success","data":{"id":9,"reference":"ULP-1","status":"success","amount":1500050,"currency":"NGN"}}'
    );
    assertSame_(1500050, $parsed['amount_minor']);
    assertSame_('successful', $parsed['status']);
    assertSame_('ULP-1', $parsed['gateway_reference']);
});

test('flutterwave amounts are converted from major to minor units', function () {
    // Flutterwave reports 15000.50, we store 1500050. Getting this wrong is a silent
    // 100x error in either direction on every single payment.
    $parsed = (new FlutterwaveGateway())->parseWebhook(
        '{"event":"charge.completed","data":{"id":9,"tx_ref":"ULP-1","status":"successful","amount":15000.50,"currency":"NGN"}}'
    );
    assertSame_(1500050, $parsed['amount_minor']);
    assertSame_('successful', $parsed['status']);
});

test('a non-success gateway status is never read as success', function () {
    $ps = (new PaystackGateway())->parseWebhook('{"data":{"id":1,"reference":"r","status":"abandoned","amount":100}}');
    assertSame_('failed', $ps['status']);
    $fw = (new FlutterwaveGateway())->parseWebhook('{"data":{"id":1,"tx_ref":"r","status":"pending","amount":1}}');
    assertSame_('failed', $fw['status']);
});

test('malformed webhook JSON does not throw', function () {
    // A gateway outage or a hostile POST must produce a null event id the caller can
    // reject, not a 500 that looks like our bug.
    $parsed = (new PaystackGateway())->parseWebhook('not json at all');
    assertSame_(null, $parsed['event_id']);
});

// --------------------------------------------- amount check before crediting

test('assertAmountMatches refuses to credit when the gateway amount differs', function () {
    require_once dirname(__DIR__) . '/app/core/PaymentService.php';
    $m = (new ReflectionClass(PaymentService::class))->getMethod('assertAmountMatches');
    $m->setAccessible(true);

    $payment = ['amount' => 500000, 'currency' => 'NGN'];

    assertSame_('', $m->invoke(null, $payment, 500000, 'NGN'), 'a matching amount is accepted');
    assertSame_('', $m->invoke(null, $payment, 500000, 'ngn'), 'currency comparison is case-insensitive');
    assertSame_('', $m->invoke(null, $payment, null, null), 'a gateway that omits the amount is not treated as a mismatch');

    assertTrue_($m->invoke(null, $payment, 100, 'NGN') !== '', 'underpayment is caught');
    assertTrue_($m->invoke(null, $payment, 50000000, 'NGN') !== '', 'overpayment is caught');
    assertTrue_($m->invoke(null, $payment, 500000, 'USD') !== '', 'a currency swap is caught');
});
