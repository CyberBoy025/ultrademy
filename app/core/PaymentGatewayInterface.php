<?php
declare(strict_types=1);

/**
 * One interface, one implementation per provider, zero provider names in business code
 * (05-finance-payments.md §4). Adding a provider is a new class plus a settings row;
 * PaymentService does not change.
 *
 * Manual payment is implemented as a gateway too. That is deliberate — it keeps §27's
 * bank-transfer flow from becoming a special case threaded through the codebase. It has
 * the same lifecycle; it just has a human where the API call would be.
 */
interface PaymentGatewayInterface
{
    /** Matches `payments.method`. */
    public function code(): string;

    public function label(): string;

    /** False when credentials are missing — the UI must not offer an unusable method. */
    public function isConfigured(): bool;

    /**
     * Starts a charge.
     * @return array{authorisation_url:?string,gateway_reference:?string,error:?string}
     */
    public function initialise(array $invoice, array $payer, string $reference, int $amountMinor, string $currency): array;

    /**
     * Server-side truth. Called by the webhook handler and by reconciliation.
     * @return array{status:string,amount_minor:?int,currency:?string,error:?string}
     *         status is one of: successful | failed | pending | unknown
     */
    public function verify(string $gatewayReference): array;

    /** @param array<string,string> $headers lower-cased header names */
    public function verifySignature(string $rawBody, array $headers): bool;

    /**
     * @return array{event_id:?string,event_type:?string,gateway_reference:?string,status:?string,amount_minor:?int,currency:?string}
     */
    public function parseWebhook(string $rawBody): array;
}
