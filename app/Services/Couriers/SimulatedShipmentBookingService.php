<?php

namespace App\Services\Couriers;

use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\PaymentMethod;
use App\Enums\ShipmentBookingStatus;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use Illuminate\Support\Str;
use RuntimeException;

class SimulatedShipmentBookingService
{
    public function __construct(
        private readonly ShipmentPersistenceService $persistence,
    ) {}

    public function book(Order $order, CourierProvider $courierProvider): Shipping
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

        $request = new ShipmentBookingRequestDTO(
            order: $order->loadMissing('items'),
            weightKg: $courierProvider->default_package_weight ? (float) $courierProvider->default_package_weight : null,
            pieces: max(1, (int) $order->items->sum('quantity')),
            codAmount: $this->codAmountFor($order),
        );

        $result = $this->simulateResult($order, $courierProvider, $request);

        return $this->persistence->persistBooking($order, $courierProvider, $result, simulated: true);
    }

    private function simulateResult(
        Order $order,
        CourierProvider $courierProvider,
        ShipmentBookingRequestDTO $request,
    ): ShipmentBookingResultDTO {
        $prefix = str($courierProvider->slug)->upper()->replace('_', '')->substr(0, 4)->toString();
        $suffix = strtoupper(Str::random(8));

        return new ShipmentBookingResultDTO(
            status: ShipmentBookingStatus::Booked,
            externalShipmentId: 'SIM-'.$order->order_number.'-'.strtoupper(Str::random(6)),
            trackingNumber: $prefix.$suffix,
            awbNumber: 'AWB'.$suffix,
            message: 'Simulated booking completed. No external courier API was called.',
            metadata: [
                'simulated' => true,
                'provider_slug' => $courierProvider->slug,
                'weight_kg' => $request->weightKg,
                'pieces' => $request->pieces,
                'cod_amount' => $request->codAmount,
                'booked_at' => now()->toIso8601String(),
            ],
        );
    }

    private function codAmountFor(Order $order): ?float
    {
        if ($order->payment_method === PaymentMethod::Cod) {
            return (float) $order->grand_total;
        }

        return null;
    }
}
