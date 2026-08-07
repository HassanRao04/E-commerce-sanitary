<?php

namespace App\Services\Couriers;

use App\DataTransferObjects\CourierTrackingResultDTO;
use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\ShipmentBookingStatus;
use App\Models\Shipping;

class ManualCourierProvider extends AbstractCourierService
{
    public function slug(): string
    {
        return 'manual';
    }

    public function displayName(): string
    {
        return 'Manual';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function bookShipment(ShipmentBookingRequestDTO $request): ShipmentBookingResultDTO
    {
        return new ShipmentBookingResultDTO(
            status: ShipmentBookingStatus::Manual,
            message: 'Manual shipments are created through the admin shipping workflow.',
        );
    }

    public function fetchTracking(Shipping $shipment): CourierTrackingResultDTO
    {
        return new CourierTrackingResultDTO(
            success: true,
            message: 'Manual shipments use admin-entered tracking events.',
            events: $shipment->trackingEvents()
                ->chronological()
                ->get()
                ->map(fn ($event): array => [
                    'status' => $event->status,
                    'location' => $event->location,
                    'description' => $event->description,
                    'event_at' => $event->event_at?->toIso8601String(),
                ])
                ->all(),
        );
    }
}
