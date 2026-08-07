<?php

namespace App\Contracts;

use App\DataTransferObjects\CourierTrackingResultDTO;
use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Models\Shipping;

interface CourierInterface
{
    public function slug(): string;

    public function displayName(): string;

    public function isEnabled(): bool;

    public function bookShipment(ShipmentBookingRequestDTO $request): ShipmentBookingResultDTO;

    public function cancelShipment(Shipping $shipment): ShipmentBookingResultDTO;

    public function fetchTracking(Shipping $shipment): CourierTrackingResultDTO;

    public function fetchLabel(Shipping $shipment): ?string;
}
