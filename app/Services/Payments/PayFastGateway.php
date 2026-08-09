<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

/**
 * Webhook signature verification is NOT implemented. PayFast ITN validation
 * requires the parameter signature built from `payments.payfast.passphrase`
 * plus a server-to-server validate callback, neither of which is derivable from
 * this codebase. verifySignature() therefore inherits the fail-closed default,
 * so every webhook is rejected. Implement both and enable
 * PAYMENT_PAYFAST_ENABLED together.
 */
class PayFastGateway extends BasePaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Payfast;
    }

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            gatewayReference: $intent->order->order_number,
            redirectUrl: route('shop.payment.show', $intent->order),
            message: 'PayFast integration pending.',
        );
    }

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO
    {
        $payload = $webhook->payload;
        $reference = $payload['m_payment_id'] ?? $payload['item_name'] ?? $payload['order_number'] ?? null;
        $transactionId = $payload['pf_payment_id'] ?? $payload['transaction_id'] ?? null;
        $status = strtolower((string) ($payload['payment_status'] ?? $payload['status'] ?? ''));

        $successful = in_array($status, ['complete', 'paid', 'success'], true);

        return new PaymentVerificationDTO(
            status: $successful ? PaymentStatus::Paid : PaymentStatus::Failed,
            transactionId: is_string($transactionId) ? (string) $transactionId : null,
            gatewayReference: is_string($reference) ? $reference : null,
            message: $payload['message'] ?? 'PayFast webhook processed.',
            metadata: ['webhook' => $payload],
        );
    }
}
