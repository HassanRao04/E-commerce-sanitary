<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentWebhookDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

/**
 * Webhook signature verification is NOT implemented. JazzCash signs callbacks
 * with `pp_SecureHash` derived from `payments.jazzcash.integrity_salt`, but the
 * exact field ordering is contract-specific and is not derivable from this
 * codebase. verifySignature() therefore inherits the fail-closed default, so
 * every webhook is rejected. Implement the merchant-specific hash and enable
 * PAYMENT_JAZZCASH_ENABLED together, never separately.
 */
class JazzCashGateway extends BasePaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Jazzcash;
    }

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            gatewayReference: $intent->order->order_number,
            redirectUrl: route('shop.payment.show', $intent->order),
            message: 'JazzCash integration pending.',
        );
    }

    public function handleWebhook(PaymentWebhookDTO $webhook): PaymentVerificationDTO
    {
        $payload = $webhook->payload;
        $reference = $payload['pp_BillReference'] ?? $payload['ppmpf_1'] ?? $payload['order_number'] ?? null;
        $transactionId = $payload['pp_TxnRefNo'] ?? $payload['transaction_id'] ?? null;
        $responseCode = (string) ($payload['pp_ResponseCode'] ?? '');

        $successful = in_array($responseCode, ['000', '121', '200'], true)
            || ($payload['status'] ?? null) === 'paid';

        return new PaymentVerificationDTO(
            status: $successful ? PaymentStatus::Paid : PaymentStatus::Failed,
            transactionId: is_string($transactionId) ? $transactionId : null,
            gatewayReference: is_string($reference) ? $reference : null,
            message: $payload['pp_ResponseMessage'] ?? $payload['message'] ?? 'JazzCash webhook processed.',
            metadata: ['webhook' => $payload],
        );
    }
}
