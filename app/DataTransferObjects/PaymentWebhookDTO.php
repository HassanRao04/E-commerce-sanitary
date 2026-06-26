<?php

namespace App\DataTransferObjects;

use App\Enums\PaymentMethod;

readonly class PaymentWebhookDTO
{
    public function __construct(
        public PaymentMethod $gateway,
        public ?string $eventType,
        public array $payload,
    ) {}
}
