<?php

namespace App\DataTransferObjects;

use App\Enums\PaymentMethod;
use App\Models\Order;

readonly class PaymentIntentDTO
{
    public function __construct(
        public Order $order,
        public PaymentMethod $method,
        public float $amount,
        public string $currency,
        public array $metadata = [],
    ) {}
}
