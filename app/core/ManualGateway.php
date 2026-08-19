<?php
declare(strict_types=1);

/**
 * Bank transfer and cash. A human performs the verification an API would otherwise do,
 * but the lifecycle is identical, which is the whole point of implementing the interface
 * (05-finance-payments.md §4).
 */
final class ManualGateway implements PaymentGatewayInterface
{
    public function __construct(private string $method = 'bank_transfer')
    {
    }

    public function code(): string
    {
        return $this->method;
    }

    public function label(): string
    {
        return $this->method === 'cash' ? 'Cash' : 'Bank Transfer';
    }

    /** Always available — it needs bank details, not API credentials. */
    public function isConfigured(): bool
    {
        return true;
    }

    public function initialise(array $invoice, array $payer, string $reference, int $amountMinor, string $currency): array
    {
        // No redirect. The payer is shown bank details and submits a reference afterwards.
        return ['authorisation_url' => null, 'gateway_reference' => null, 'error' => null];
    }

    /** There is nothing to ask; a person decides. */
    public function verify(string $gatewayReference): array
    {
        return ['status' => 'pending', 'amount_minor' => null, 'currency' => null, 'error' => null];
    }

    public function verifySignature(string $rawBody, array $headers): bool
    {
        return false; // manual payments never arrive by webhook
    }

    public function parseWebhook(string $rawBody): array
    {
        return ['event_id' => null, 'event_type' => null, 'gateway_reference' => null,
                'status' => null, 'amount_minor' => null, 'currency' => null];
    }
}
