<?php

namespace App\Services\Payments;

use App\DataTransferObjects\PaymentIntentDTO;
use App\DataTransferObjects\PaymentVerificationDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Support\Str;

class CashOnDeliveryGateway extends BasePaymentGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Cod;
    }

    public function charge(PaymentIntentDTO $intent): PaymentVerificationDTO
    {
        return new PaymentVerificationDTO(
            status: PaymentStatus::Pending,
            transactionId: 'COD-'.Str::upper(Str::random(10)),
            gatewayReference: $intent->order->order_number,
            message: 'Payment will be collected on delivery.',
        );
    }
}
