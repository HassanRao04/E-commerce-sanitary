<?php

namespace App\DataTransferObjects;

use App\Enums\ShipmentBookingStatus;
use App\Models\Order;

readonly class ShipmentBookingRequestDTO
{
    public function __construct(
        public Order $order,
        public ?float $weightKg = null,
        public ?int $pieces = null,
        public ?float $codAmount = null,
        public array $metadata = [],
    ) {}
}
