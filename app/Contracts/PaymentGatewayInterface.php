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

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO;

    public function refund(RefundDTO $refund): PaymentVerificationDTO;
}
