<?php

namespace App\Contracts;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\RefundDTO;
use App\Enums\PaymentMethod;

interface PaymentGatewayInterface
{
    public function method(): PaymentMethod;

    public function isEnabled(): bool;

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO;

    public function verify(string $reference, array $payload = []): PaymentVerificationDTO;

    /**
     * Authenticate an inbound webhook against the provider's signing scheme.
     *
     * Implementations must verify against $webhook->rawBody, never the parsed
     * payload, and must return false whenever the signature, secret or scheme
     * is missing. Callers treat false as a hard rejection.
     */
    public function verifySignature(PaymentWebhookDTO $webhook): bool;

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO;

    public function refund(RefundDTO $refund): PaymentVerificationDTO;
}
