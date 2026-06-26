<?php

namespace App\DataTransferObjects;

use App\Models\Order;
use App\Models\Payment;

readonly class RefundDTO
{
    public function __construct(
        public Payment $payment,
        public Order $order,
        public float $amount,
        public ?string $reason = null,
    ) {}
}
