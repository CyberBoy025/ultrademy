<?php
declare(strict_types=1);

/**
 * Paystack.
 *
 * Paystack works in MINOR units (kobo), which matches our storage — so amounts pass
 * through unconverted. Contrast FlutterwaveGateway, which does not.
 *
 * Signature: HMAC-SHA512 of the RAW request body, keyed with the secret key, compared to
 * the `x-paystack-signature` header. The raw body matters — re-encoding the parsed JSON
 * produces a different string and the signature will never match.
 */
final class PaystackGateway implements PaymentGatewayInterface
{
    private const API = 'https://api.paystack.co';

    public function code(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Card / Bank (Paystack)';
    }

    public function isConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    private function secretKey(): string
    {
        return trim((string) (Setting::get('paystack_secret_key', '') ?? ''));
    }

    public function initialise(array $invoice, array $payer, string $reference, int $amountMinor, string $currency): array
    {
        if (!$this->isConfigured()) {
            return ['authorisation_url' => null, 'gateway_reference' => null, 'error' => 'Paystack is not configured.'];
        }
        $res = $this->request('POST', '/transaction/initialize', [
            'email'     => $payer['email'],
            'amount'    => $amountMinor,           // kobo — no conversion
            'currency'  => $currency,
            'reference' => $reference,
            'callback_url' => rtrim((string) config('app.url'), '/') . '/payment-return.php',
        ]);
        if (($res['status'] ?? false) !== true) {
            return ['authorisation_url' => null, 'gateway_reference' => null, 'error' => $res['message'] ?? 'Paystack error.'];
        }
        return [
            'authorisation_url' => $res['data']['authorization_url'] ?? null,
            'gateway_reference' => $res['data']['reference'] ?? $reference,
            'error' => null,
        ];
    }

    public function verify(string $gatewayReference): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'unknown', 'amount_minor' => null, 'currency' => null, 'error' => 'Paystack is not configured.'];
        }
        $res = $this->request('GET', '/transaction/verify/' . rawurlencode($gatewayReference));
        if (($res['status'] ?? false) !== true) {
            return ['status' => 'unknown', 'amount_minor' => null, 'currency' => null, 'error' => $res['message'] ?? 'Verify failed.'];
        }
        $data = $res['data'] ?? [];
        return [
            'status' => ($data['status'] ?? '') === 'success' ? 'successful' : (($data['status'] ?? '') === 'failed' ? 'failed' : 'pending'),
            'amount_minor' => isset($data['amount']) ? (int) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
            'error' => null,
        ];
    }

    public function verifySignature(string $rawBody, array $headers): bool
    {
        $secret = $this->secretKey();
        $sent = $headers['x-paystack-signature'] ?? '';
        if ($secret === '' || $sent === '') {
            return false;
        }
        return hash_equals(hash_hmac('sha512', $rawBody, $secret), $sent);
    }

    public function parseWebhook(string $rawBody): array
    {
        $body = json_decode($rawBody, true) ?: [];
        $data = $body['data'] ?? [];
        $status = ($data['status'] ?? '') === 'success' ? 'successful' : 'failed';
        return [
            // Paystack's transaction id is stable across retries; the reference is ours.
            'event_id' => isset($data['id']) ? (string) $data['id'] : ($data['reference'] ?? null),
            'event_type' => $body['event'] ?? null,
            'gateway_reference' => $data['reference'] ?? null,
            'status' => $status,
            'amount_minor' => isset($data['amount']) ? (int) $data['amount'] : null,
            'currency' => $data['currency'] ?? null,
        ];
    }

    /** @return array<string,mixed> */
    private function request(string $method, string $path, array $payload = []): array
    {
        $ch = curl_init(self::API . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey(),
                'Content-Type: application/json',
            ],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['status' => false, 'message' => 'Network error: ' . $err];
        }
        return json_decode((string) $raw, true) ?: ['status' => false, 'message' => 'Unreadable response.'];
    }
}
