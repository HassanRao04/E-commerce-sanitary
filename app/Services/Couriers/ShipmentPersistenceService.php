<?php

namespace App\Services\Couriers;

use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\ShipmentStatus;
use App\Enums\TrackingEventSource;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Tracking;
use App\Services\ActivityLogService;
use App\Services\Admin\OrderNotificationService;
use Illuminate\Support\Facades\DB;

class ShipmentPersistenceService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly OrderNotificationService $orderNotifications,
    ) {}

    public function persistBooking(
        Order $order,
        CourierProvider $courierProvider,
        ShipmentBookingResultDTO $result,
        bool $simulated = true,
    ): Shipping {
        return DB::transaction(function () use ($order, $courierProvider, $result, $simulated): Shipping {
            $shipment = Shipping::create([
                'order_id' => $order->id,
                'courier_provider_id' => $courierProvider->id,
                'courier_name' => $courierProvider->name,
                'external_shipment_id' => $result->externalShipmentId,
                'tracking_number' => $result->trackingNumber,
                'awb_number' => $result->awbNumber,
                'label_path' => $result->labelPath,
                'status' => ShipmentStatus::Pending,
                'booking_status' => $result->status,
                'booked_at' => now(),
                'booking_meta' => $result->metadata,
            ]);

            Tracking::create([
                'shipment_id' => $shipment->id,
                'status' => $simulated ? 'Booked' : 'Booked with courier',
                'location' => $courierProvider->pickup_city,
                'description' => $result->message ?? ($simulated
                    ? 'Simulated courier booking — awaiting pickup.'
                    : 'Courier booking confirmed via API.'),
                'event_at' => now(),
                'source' => TrackingEventSource::Api,
            ]);

            $this->activityLog->log($simulated ? 'shipping.booked_simulated' : 'shipping.booked', $shipment, [], [
                'courier_provider' => $courierProvider->slug,
                'simulated' => $simulated,
            ]);

            $order->loadMissing('user');
            $this->orderNotifications->notifyShipmentUpdate(
                $order,
                $shipment->courier_name,
                $shipment->tracking_number,
            );

            return $shipment->fresh(['order', 'courierProvider']);
        });
    }
}
