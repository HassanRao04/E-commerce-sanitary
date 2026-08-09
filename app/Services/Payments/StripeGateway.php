<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

class StripeGateway extends BasePaymentGateway
{
    /** Reject signatures whose timestamp is outside this window, to block replays. */
    private const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function method(): PaymentMethod
    {
        return PaymentMethod::Stripe;
    }

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            gatewayReference: $intent->order->order_number,
            redirectUrl: route('shop.payment.show', $intent->order),
            message: 'Stripe integration pending. Complete payment manually or contact support.',
        );
    }

    /**
     * Verify the `Stripe-Signature` header per Stripe's documented scheme:
     * HMAC-SHA256 over "{timestamp}.{raw body}" keyed with the endpoint secret.
     */
    public function verifySignature(PaymentWebhookDTO $webhook): bool
    {
        $secret = config('payments.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $header = $webhook->header('Stripe-Signature');

        if (! is_string($header) || $header === '') {
            return false;
        }

        [$timestamp, $signatures] = $this->parseSignatureHeader($header);

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        if (abs(time() - $timestamp) > self::SIGNATURE_TOLERANCE_SECONDS) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$webhook->rawBody, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO
    {
        $payload = $webhook->payload;
        $object = $payload['data']['object'] ?? $payload;
        $eventType = $webhook->eventType ?? $payload['type'] ?? null;

        $reference = $object['metadata']['order_number']
            ?? $object['client_reference_id']
            ?? $payload['order_number']
            ?? null;

        $transactionId = $object['id'] ?? $payload['transaction_id'] ?? null;

        $successful = in_array($eventType, [
            'payment_intent.succeeded',
            'checkout.session.completed',
            'charge.succeeded',
        ], true) || ($object['status'] ?? null) === 'succeeded';

        return new PaymentVerificationDTO(
            status: $successful ? PaymentStatus::Paid : PaymentStatus::Pending,
            transactionId: is_string($transactionId) ? $transactionId : null,
            gatewayReference: is_string($reference) ? $reference : null,
            message: 'Stripe webhook processed.',
            metadata: ['event' => $eventType, 'webhook' => $payload],
            amount: $this->resolveAmount($object),
            currency: $this->resolveCurrency($object),
            eventId: is_string($payload['id'] ?? null) ? $payload['id'] : null,
        );
    }

    /**
     * @return array{0: int|null, 1: list<string>}
     */
    private function parseSignatureHeader(string $header): array
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);

            if (count($pair) !== 2) {
                continue;
            }

            [$key, $value] = $pair;

            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        return [$timestamp, $signatures];
    }

    /**
     * Stripe reports amounts in the smallest currency unit; PKR is a two-decimal
     * currency, so minor units are converted back to major units here.
     *
     * @param  array<string, mixed>  $object
     */
    private function resolveAmount(array $object): ?float
    {
        $minorUnits = $object['amount_received']
            ?? $object['amount_total']
            ?? $object['amount']
            ?? null;

        if (! is_int($minorUnits) && ! is_float($minorUnits)) {
            return null;
        }

        return round((float) $minorUnits / 100, 2);
    }

    /** @param  array<string, mixed>  $object */
    private function resolveCurrency(array $object): ?string
    {
        $currency = $object['currency'] ?? null;

        return is_string($currency) && $currency !== '' ? strtoupper($currency) : null;
    }
}
