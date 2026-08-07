<?php

namespace App\Services\Couriers;

use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\ShipmentBookingStatus;
use App\Enums\TrackingEventSource;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Tracking;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for future courier API booking.
 * Manual admin workflow continues to use ShippingService directly.
 */
class CourierIntegrationService
{
    public function __construct(
        private readonly CourierProviderManager $providers,
    ) {}

    public function book(Order $order, CourierProvider $courierProvider, ShipmentBookingRequestDTO $request): ShipmentBookingResultDTO
    {
        $provider = $this->providers->resolve($courierProvider->slug, requireEnabled: true);

        return $provider->bookShipment($request);
    }

    public function applyBookingResult(Shipping $shipment, ShipmentBookingResultDTO $result): Shipping
    {
        return DB::transaction(function () use ($shipment, $result): Shipping {
            $shipment->update([
                'external_shipment_id' => $result->externalShipmentId ?? $shipment->external_shipment_id,
                'tracking_number' => $result->trackingNumber ?? $shipment->tracking_number,
                'awb_number' => $result->awbNumber ?? $shipment->awb_number,
                'label_path' => $result->labelPath ?? $shipment->label_path,
                'booking_status' => $result->status,
                'booked_at' => $result->isSuccessful() ? now() : $shipment->booked_at,
                'booking_meta' => array_merge($shipment->booking_meta ?? [], $result->metadata),
            ]);

            return $shipment->fresh(['order', 'courierProvider']);
        });
    }

    public function recordProviderTrackingEvents(Shipping $shipment, array $events, TrackingEventSource $source): void
    {
        DB::transaction(function () use ($shipment, $events, $source): void {
            foreach ($events as $event) {
                Tracking::create([
                    'shipment_id' => $shipment->id,
                    'status' => $event['status'],
                    'location' => $event['location'] ?? null,
                    'description' => $event['description'] ?? null,
                    'event_at' => $event['event_at'],
                    'source' => $source->value,
                ]);
            }
        });
    }

    public function defaultBookingStatus(): ShipmentBookingStatus
    {
        return ShipmentBookingStatus::Manual;
    }
}
