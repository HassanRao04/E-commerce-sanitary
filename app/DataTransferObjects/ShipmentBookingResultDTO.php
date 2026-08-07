<?php

namespace App\DataTransferObjects;

use App\Enums\ShipmentBookingStatus;

readonly class ShipmentBookingResultDTO
{
    public function __construct(
        public ShipmentBookingStatus $status,
        public ?string $externalShipmentId = null,
        public ?string $trackingNumber = null,
        public ?string $awbNumber = null,
        public ?string $labelPath = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === ShipmentBookingStatus::Booked;
    }
}
