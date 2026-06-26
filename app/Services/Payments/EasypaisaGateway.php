<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

class EasypaisaGateway extends BasePaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Easypaisa;
    }

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            gatewayReference: $intent->order->order_number,
            redirectUrl: route('shop.payment.show', $intent->order),
            message: 'Easypaisa integration pending.',
        );
    }

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO
    {
        $payload = $webhook->payload;
        $reference = $payload['orderId'] ?? $payload['order_number'] ?? $payload['basket_id'] ?? null;
        $transactionId = $payload['transactionId'] ?? $payload['transaction_id'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? $payload['responseCode'] ?? ''));

        $successful = in_array($status, ['paid', 'success', '000', '00'], true);

        return new PaymentVerificationDTO(
            status: $successful ? PaymentStatus::Paid : PaymentStatus::Failed,
            transactionId: is_string($transactionId) ? $transactionId : null,
            gatewayReference: is_string($reference) ? $reference : null,
            message: $payload['responseMessage'] ?? 'Easypaisa webhook processed.',
            metadata: ['webhook' => $payload],
        );
    }
}
