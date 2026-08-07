<?php

namespace App\Services\Couriers;

use App\Contracts\CourierInterface;
use App\DataTransferObjects\CourierTrackingResultDTO;
use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\ShipmentBookingStatus;
use App\Models\CourierProvider;
use App\Models\Shipping;

abstract class AbstractCourierService implements CourierInterface
{
    abstract public function slug(): string;

    public function displayName(): string
    {
        return $this->providerRecord()?->name
            ?? str($this->slug())->replace('_', ' ')->title()->value();
    }

    public function isEnabled(): bool
    {
        $record = $this->providerRecord();

        if ($record) {
            return $record->is_active
                && (bool) config('couriers.enabled.'.$this->slug(), false);
        }

        return (bool) config('couriers.enabled.'.$this->slug(), false);
    }

    public function bookShipment(ShipmentBookingRequestDTO $request): ShipmentBookingResultDTO
    {
        return $this->notImplementedBooking('Booking');
    }

    public function cancelShipment(Shipping $shipment): ShipmentBookingResultDTO
    {
        return $this->notImplementedBooking('Cancellation');
    }

    public function fetchTracking(Shipping $shipment): CourierTrackingResultDTO
    {
        return new CourierTrackingResultDTO(
            success: false,
            message: 'Tracking fetch is not implemented for '.$this->displayName().'.',
        );
    }

    public function fetchLabel(Shipping $shipment): ?string
    {
        return $shipment->label_path;
    }

    protected function providerRecord(): ?CourierProvider
    {
        return CourierProvider::query()->where('slug', $this->slug())->first();
    }

    protected function notImplementedBooking(string $action): ShipmentBookingResultDTO
    {
        return new ShipmentBookingResultDTO(
            status: ShipmentBookingStatus::Failed,
            message: "{$action} is not implemented for {$this->displayName()}.",
        );
    }
}
