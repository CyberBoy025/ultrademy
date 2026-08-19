<?php
/**
 * Gateway webhook receiver — the ONLY untrusted input that is allowed to change a
 * payment's status, and only after its signature is verified.
 *
 *   POST /webhook.php?provider=paystack
 *   POST /webhook.php?provider=flutterwave
 *
 * Deliberately outside app.php: it has no session, no CSRF token and no logged-in user.
 * Running it through the authenticated front controller would mean either weakening that
 * controller or bolting on an exception — and exceptions in an auth path are how holes
 * appear.
 */
require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.\n");
}

$provider = (string) ($_GET['provider'] ?? '');
$rawBody = (string) file_get_contents('php://input');

// The RAW body is what the signature covers. Re-encoding parsed JSON produces a
// different byte string and the HMAC will never match.
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
    }
}

try {
    $result = PaymentService::handleWebhook($provider, $rawBody, $headers, $_SERVER['REMOTE_ADDR'] ?? null);
} catch (Throwable $e) {
    // Never leak internals to a gateway (or to anyone spraying this endpoint).
    error_log('[webhook] ' . $e->getMessage());
    http_response_code(500);
    exit("Error.\n");
}

http_response_code($result['http']);
echo $result['message'], "\n";
