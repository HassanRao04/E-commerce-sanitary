<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\RefundDTO;
use App\Enums\PaymentStatus;

abstract class BasePaymentGateway implements PaymentGatewayInterface
{
    protected function configKey(): string
    {
        return $this->method()->value;
    }

    public function isEnabled(): bool
    {
        return (bool) config('payments.enabled.'.$this->configKey(), false);
    }

    public function verify(string $reference, array $payload = []): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            gatewayReference: $reference,
            message: 'Verification not implemented for '.$this->method()->value,
        );
    }

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            message: 'Webhook handling not implemented for '.$this->method()->value,
        );
    }

    public function refund(RefundDTO $refund): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            message: 'Refund not implemented for '.$this->method()->value,
        );
    }
}
