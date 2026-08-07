<?php

namespace App\Services\Couriers;

use App\DataTransferObjects\CourierTrackingResultDTO;
use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\PaymentMethod;
use App\Enums\TrackingEventSource;
use App\Enums\ShipmentBookingStatus;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ShipmentBookingService
{
    public function __construct(
        private readonly CourierService $courierService,
        private readonly SimulatedShipmentBookingService $simulatedBooking,
        private readonly ShipmentPersistenceService $persistence,
    ) {}

    public function bookForOrder(Order $order, CourierProvider $courierProvider): Shipping
    {
        if ($courierProvider->slug === 'manual') {
            throw new RuntimeException('Manual shipments must be created using the manual form.');
        }

        if ($order->shipments()->exists()) {
            throw new RuntimeException('This order already has a shipment.');
        }

        if (! $courierProvider->is_active) {
            throw new RuntimeException('Selected courier provider is not active.');
        }

        if ($courierProvider->slug === 'tcs' && $courierProvider->isConfigured()) {
            return $this->bookViaProviderApi($order, $courierProvider);
        }

        return $this->simulatedBooking->book($order, $courierProvider);
    }

    public function syncTracking(Shipping $shipment): CourierTrackingResultDTO
    {
        $result = $this->courierService->track($shipment);

        if ($result->success && $result->events !== []) {
            app(CourierIntegrationService::class)->recordProviderTrackingEvents(
                $shipment,
                $result->events,
                TrackingEventSource::Api,
            );
        }

        return $result;
    }

    public function downloadCourierLabel(Shipping $shipment): string
    {
        $path = $this->courierService->label($shipment);

        if (blank($path) || ! Storage::disk('local')->exists($path)) {
            throw new RuntimeException('Courier label is not available for this shipment.');
        }

        return Storage::disk('local')->path($path);
    }

    private function bookViaProviderApi(Order $order, CourierProvider $courierProvider): Shipping
    {
        $request = $this->buildRequest($order, $courierProvider);
        $result = $this->courierService->book($order, $courierProvider, $request);

        if (! $result->isSuccessful()) {
            throw new RuntimeException($result->message ?? 'Courier booking failed.');
        }

        return $this->persistence->persistBooking($order, $courierProvider, $result, simulated: false);
    }

    private function buildRequest(Order $order, CourierProvider $courierProvider): ShipmentBookingRequestDTO
    {
        return new ShipmentBookingRequestDTO(
            order: $order->loadMissing('items'),
            weightKg: $courierProvider->default_package_weight ? (float) $courierProvider->default_package_weight : null,
            pieces: max(1, (int) $order->items->sum('quantity')),
            codAmount: $order->payment_method === PaymentMethod::Cod ? (float) $order->grand_total : null,
        );
    }
}
