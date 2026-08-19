<?php
declare(strict_types=1);

/**
 * Flutterwave.
 *
 * THE UNIT TRAP: Flutterwave works in MAJOR units (naira as a decimal), where Paystack
 * uses minor units. Passing our stored kobo straight through would charge 100× the
 * intended amount. Every conversion in this class is therefore explicit and one-way:
 *
 *     to gateway   : intdiv/► $minor / 100   (kobo → naira)
 *     from gateway : round($major * 100)     (naira → kobo)
 *
 * Signature: Flutterwave sends a `verif-hash` header that must equal a secret hash you
 * configure in their dashboard. It is a shared secret comparison, not an HMAC of the
 * body — weaker than Paystack's scheme, which is why amount and currency are re-checked
 * against our own record before anything is credited.
 */
final class FlutterwaveGateway implements PaymentGatewayInterface
{
    private const API = 'https://api.flutterwave.com/v3';

    public function code(): string
    {
        return 'flutterwave';
    }

    public function label(): string
    {
        return 'Card / Bank (Flutterwave)';
    }

    public function isConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    private function secretKey(): string
    {
        return trim((string) (Setting::get('flutterwave_secret_key', '') ?? ''));
    }

    private function webhookHash(): string
    {
        return trim((string) (Setting::get('flutterwave_webhook_hash', '') ?? ''));
    }

    public function initialise(array $invoice, array $payer, string $reference, int $amountMinor, string $currency): array
    {
        if (!$this->isConfigured()) {
            return ['authorisation_url' => null, 'gateway_reference' => null, 'error' => 'Flutterwave is not configured.'];
        }
        $res = $this->request('POST', '/payments', [
            'tx_ref' => $reference,
            'amount' => number_format($amountMinor / 100, 2, '.', ''), // minor → MAJOR
            'currency' => $currency,
            'redirect_url' => rtrim((string) config('app.url'), '/') . '/payment-return.php',
            'customer' => [
                'email' => $payer['email'],
                'name'  => trim(($payer['first_name'] ?? '') . ' ' . ($payer['last_name'] ?? '')),
            ],
        ]);
        if (($res['status'] ?? '') !== 'success') {
            return ['authorisation_url' => null, 'gateway_reference' => null, 'error' => $res['message'] ?? 'Flutterwave error.'];
        }
        return [
            'authorisation_url' => $res['data']['link'] ?? null,
            'gateway_reference' => $reference,
            'error' => null,
        ];
    }

    public function verify(string $gatewayReference): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'unknown', 'amount_minor' => null, 'currency' => null, 'error' => 'Flutterwave is not configured.'];
        }
        $res = $this->request('GET', '/transactions/verify_by_reference?tx_ref=' . rawurlencode($gatewayReference));
        if (($res['status'] ?? '') !== 'success') {
            return ['status' => 'unknown', 'amount_minor' => null, 'currency' => null, 'error' => $res['message'] ?? 'Verify failed.'];
        }
        $data = $res['data'] ?? [];
        $flwStatus = strtolower((string) ($data['status'] ?? ''));
        return [
            'status' => $flwStatus === 'successful' ? 'successful' : ($flwStatus === 'failed' ? 'failed' : 'pending'),
            'amount_minor' => isset($data['amount']) ? (int) round(((float) $data['amount']) * 100) : null, // MAJOR → minor
            'currency' => $data['currency'] ?? null,
            'error' => null,
        ];
    }

    public function verifySignature(string $rawBody, array $headers): bool
    {
        $expected = $this->webhookHash();
        $sent = $headers['verif-hash'] ?? '';
        if ($expected === '' || $sent === '') {
            return false;
        }
        return hash_equals($expected, $sent);
    }

    public function parseWebhook(string $rawBody): array
    {
        $body = json_decode($rawBody, true) ?: [];
        $data = $body['data'] ?? [];
        $flwStatus = strtolower((string) ($data['status'] ?? ''));
        return [
            'event_id' => isset($data['id']) ? (string) $data['id'] : ($data['tx_ref'] ?? null),
            'event_type' => $body['event'] ?? ($body['event.type'] ?? null),
            'gateway_reference' => $data['tx_ref'] ?? null,
            'status' => $flwStatus === 'successful' ? 'successful' : 'failed',
            'amount_minor' => isset($data['amount']) ? (int) round(((float) $data['amount']) * 100) : null, // MAJOR → minor
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
            return ['status' => 'error', 'message' => 'Network error: ' . $err];
        }
        return json_decode((string) $raw, true) ?: ['status' => 'error', 'message' => 'Unreadable response.'];
    }
}
