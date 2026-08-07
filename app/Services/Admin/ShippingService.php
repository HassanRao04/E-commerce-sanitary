<?php

namespace App\Services\Admin;

use App\Enums\ShipmentBookingStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TrackingEventSource;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\Tracking;
use App\Repositories\Contracts\ShippingRepositoryInterface;
use App\Services\ActivityLogService;
use App\Services\OrderWorkflowService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShippingService
{
    public function __construct(
        private readonly ShippingRepositoryInterface $shipping,
        private readonly ActivityLogService $activityLog,
        private readonly OrderNotificationService $orderNotifications,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->shipping->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function createForOrder(Order $order, array $data): Shipping
    {
        return DB::transaction(function () use ($order, $data) {
            $status = $data['status'] instanceof ShipmentStatus
                ? $data['status']
                : ShipmentStatus::from($data['status']);

            $shipment = Shipping::create([
                'order_id' => $order->id,
                'courier_provider_id' => $data['courier_provider_id'] ?? null,
                'courier_name' => $data['courier_name'],
                'tracking_number' => $data['tracking_number'] ?? null,
                'status' => $status,
                'booking_status' => ShipmentBookingStatus::Manual,
                'shipped_at' => in_array($status, [
                    ShipmentStatus::InTransit,
                    ShipmentStatus::OutForDelivery,
                    ShipmentStatus::Delivered,
                ], true) ? now() : null,
            ]);

            if ($status !== ShipmentStatus::Pending) {
                $this->syncOrderStatus($order, $status);
            }

            $this->activityLog->log('shipping.created', $shipment);

            $order->loadMissing('user');
            $this->orderNotifications->notifyShipmentUpdate(
                $order,
                $shipment->courier_name,
                $shipment->tracking_number,
            );

            return $shipment->fresh('order');
        });
    }

    public function update(Shipping $shipment, array $data): Shipping
    {
        return DB::transaction(function () use ($shipment, $data) {
            $old = $shipment->toArray();
            $status = $data['status'] instanceof ShipmentStatus
                ? $data['status']
                : ShipmentStatus::from($data['status']);

            $shipment->update([
                'courier_name' => $data['courier_name'],
                'tracking_number' => $data['tracking_number'] ?? null,
                'status' => $status,
                'shipped_at' => $shipment->shipped_at ?? (
                    in_array($status, [
                        ShipmentStatus::InTransit,
                        ShipmentStatus::OutForDelivery,
                        ShipmentStatus::Delivered,
                    ], true) ? now() : null
                ),
                'delivered_at' => $status === ShipmentStatus::Delivered ? now() : $shipment->delivered_at,
            ]);

            $this->syncOrderStatus($shipment->order, $status);
            $this->activityLog->log('shipping.updated', $shipment, $old, $shipment->toArray());

            return $shipment->fresh('order');
        });
    }

    public function addTrackingEvent(Shipping $shipment, array $data): Tracking
    {
        return DB::transaction(function () use ($shipment, $data) {
            $event = Tracking::create([
                'shipment_id' => $shipment->id,
                'status' => $data['status'],
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
                'event_at' => $data['event_at'],
                'source' => TrackingEventSource::Manual,
            ]);

            $this->activityLog->log('shipping.tracking_event', $event);

            return $event;
        });
    }

    private function syncOrderStatus(Order $order, ShipmentStatus $status): void
    {
        $orderStatus = match ($status) {
            ShipmentStatus::Delivered => $this->workflow->slugForShipmentDelivered(),
            ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::Picked => $this->workflow->slugForShipmentShipped(),
            default => null,
        };

        if ($orderStatus && ! $this->workflow->isCancelled($order->status)) {
            $order->update(['status' => $orderStatus]);
        }
    }
}
