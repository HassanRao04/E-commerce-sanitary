<?php

namespace App\DataTransferObjects;

use App\Enums\PaymentStatus;

readonly class PaymentVerificationDTO
{
    public function __construct(
        public PaymentStatus $status,
        public ?string $transactionId = null,
        public ?string $gatewayReference = null,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }
}
