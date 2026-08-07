<?php

namespace App\Services\Couriers;

use App\Contracts\CourierInterface;
use App\DataTransferObjects\CourierTrackingResultDTO;
use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;

/**
 * Public entry point for courier operations.
 * Order and shipping workflows should depend on this service — not individual providers.
 */
class CourierService
{
    public function __construct(
        private readonly CourierProviderManager $manager,
        private readonly CourierIntegrationService $integration,
    ) {}

    public function provider(string $slug): CourierInterface
    {
        return $this->manager->resolve($slug);
    }

    public function providerFor(CourierProvider $courierProvider): CourierInterface
    {
        return $this->manager->resolve($courierProvider->slug);
    }

    public function providerForShipment(Shipping $shipment): CourierInterface
    {
        return $this->manager->resolveForShipment($shipment->courierProvider);
    }

    /** @return array<CourierInterface> */
    public function enabledProviders(): array
    {
        return $this->manager->enabled();
    }

    public function book(Order $order, CourierProvider $courierProvider, ShipmentBookingRequestDTO $request): ShipmentBookingResultDTO
    {
        return $this->integration->book($order, $courierProvider, $request);
    }

    public function cancel(Shipping $shipment): ShipmentBookingResultDTO
    {
        $provider = $this->providerForShipment($shipment);

        return $provider->cancelShipment($shipment);
    }

    public function track(Shipping $shipment): CourierTrackingResultDTO
    {
        $provider = $this->providerForShipment($shipment);

        return $provider->fetchTracking($shipment);
    }

    public function label(Shipping $shipment): ?string
    {
        $provider = $this->providerForShipment($shipment);

        return $provider->fetchLabel($shipment);
    }

    public function applyBookingResult(Shipping $shipment, ShipmentBookingResultDTO $result): Shipping
    {
        return $this->integration->applyBookingResult($shipment, $result);
    }

    public function bookSimulated(Order $order, CourierProvider $courierProvider): Shipping
    {
        return app(SimulatedShipmentBookingService::class)->book($order, $courierProvider);
    }
}
