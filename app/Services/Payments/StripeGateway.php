<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

class StripeGateway extends BasePaymentGateway
{
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
        );
    }
}
