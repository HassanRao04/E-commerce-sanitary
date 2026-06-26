<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Support\Str;

class BankTransferGateway extends BasePaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::BankTransfer;
    }

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            transactionId: 'BT-'.Str::upper(Str::random(10)),
            gatewayReference: $intent->order->order_number,
            redirectUrl: route('shop.payment.show', $intent->order),
            message: 'Awaiting bank transfer confirmation.',
            metadata: config('payments.bank_transfer'),
        );
    }
}
