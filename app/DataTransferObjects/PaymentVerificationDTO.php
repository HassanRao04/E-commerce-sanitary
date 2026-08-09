<?php

namespace App\DataTransferObjects;

use App\Enums\PaymentStatus;

readonly class PaymentVerificationDTO
{
    /**
     * @param  float|null  $amount  Provider-confirmed amount in major currency units.
     * @param  string|null  $currency  ISO 4217 code, uppercase.
     * @param  string|null  $eventId  Provider event identifier, used as the replay/idempotency key.
     */
    public function __construct(
        public PaymentStatus $status,
        public ?string $transactionId = null,
        public ?string $gatewayReference = null,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public array $metadata = [],
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $eventId = null,
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
