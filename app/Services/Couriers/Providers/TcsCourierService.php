<?php

namespace App\Services\Couriers\Providers;

use App\DataTransferObjects\CourierTrackingResultDTO;
use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\DataTransferObjects\ShipmentBookingResultDTO;
use App\Enums\ShipmentBookingStatus;
use App\Models\Shipping;
use App\Services\Couriers\AbstractCourierService;
use App\Services\Couriers\Http\TcsApiClient;
use App\Services\Couriers\TcsBookingPayloadFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TcsCourierService extends AbstractCourierService
{
    public function slug(): string
    {
        return 'tcs';
    }

    public function isEnabled(): bool
    {
        $record = $this->providerRecord();

        if (! $record || ! $record->is_active) {
            return false;
        }

        return $record->isConfigured() || (bool) config('couriers.enabled.tcs', false);
    }

    public function bookShipment(ShipmentBookingRequestDTO $request): ShipmentBookingResultDTO
    {
        $provider = $this->providerRecord();

        if (! $provider?->isConfigured()) {
            return new ShipmentBookingResultDTO(
                status: ShipmentBookingStatus::Failed,
                message: 'TCS API credentials are not configured. Add API Key, Secret, and Base URL in Courier Providers.',
            );
        }

        try {
            $client = new TcsApiClient($provider);
            $payload = app(TcsBookingPayloadFactory::class)->make($request, $provider);
            $response = $client->createBooking($payload);
            $consignmentNo = $response['consignmentNo'];

            return new ShipmentBookingResultDTO(
                status: ShipmentBookingStatus::Booked,
                externalShipmentId: $consignmentNo,
                trackingNumber: $consignmentNo,
                awbNumber: $consignmentNo,
                message: (string) ($response['message'] ?? 'TCS booking confirmed.'),
                metadata: [
                    'simulated' => false,
                    'provider_slug' => 'tcs',
                    'provider_response' => $response['raw'] ?? $response,
                    'booked_at' => now()->toIso8601String(),
                ],
            );
        } catch (Throwable $exception) {
            return new ShipmentBookingResultDTO(
                status: ShipmentBookingStatus::Failed,
                message: $exception->getMessage(),
                metadata: [
                    'provider_slug' => 'tcs',
                    'error' => $exception->getMessage(),
                ],
            );
        }
    }

    public function fetchTracking(Shipping $shipment): CourierTrackingResultDTO
    {
        $provider = $this->providerRecord();

        if (! $provider?->isConfigured()) {
            return new CourierTrackingResultDTO(
                success: false,
                message: 'TCS API credentials are not configured.',
            );
        }

        $consignmentNo = $shipment->tracking_number ?? $shipment->external_shipment_id;

        if (blank($consignmentNo)) {
            return new CourierTrackingResultDTO(
                success: false,
                message: 'Shipment does not have a TCS consignment number.',
            );
        }

        try {
            $client = new TcsApiClient($provider);
            $payload = $client->trackConsignment((string) $consignmentNo);
            $events = $this->mapTrackingEvents($payload);

            return new CourierTrackingResultDTO(
                success: true,
                message: 'Tracking synced from TCS.',
                events: $events,
                metadata: [
                    'provider_slug' => 'tcs',
                    'consignment_no' => $consignmentNo,
                ],
            );
        } catch (Throwable $exception) {
            return new CourierTrackingResultDTO(
                success: false,
                message: $exception->getMessage(),
            );
        }
    }

    public function fetchLabel(Shipping $shipment): ?string
    {
        if (filled($shipment->label_path) && Storage::disk('local')->exists($shipment->label_path)) {
            return $shipment->label_path;
        }

        $provider = $this->providerRecord();

        if (! $provider?->isConfigured()) {
            return $shipment->label_path;
        }

        $consignmentNo = $shipment->tracking_number ?? $shipment->external_shipment_id;

        if (blank($consignmentNo)) {
            return null;
        }

        try {
            $client = new TcsApiClient($provider);
            $pdf = $client->downloadLabel((string) $consignmentNo);
            $path = 'courier-labels/tcs/'.$shipment->id.'-'.$consignmentNo.'.pdf';

            Storage::disk('local')->put($path, $pdf);
            $shipment->update(['label_path' => $path]);

            return $path;
        } catch (Throwable) {
            return $shipment->label_path;
        }
    }

    /** @return list<array{status: string, location: ?string, description: ?string, event_at: string}> */
    private function mapTrackingEvents(array $payload): array
    {
        $rows = collect(data_get($payload, 'checkpoints', []))
            ->whenEmpty(fn ($collection) => collect(data_get($payload, 'deliveryinfo', [])));

        return $rows
            ->map(function (array $row): ?array {
                $status = (string) ($row['status'] ?? '');
                $datetime = (string) ($row['datetime'] ?? '');

                if ($status === '' || $datetime === '') {
                    return null;
                }

                return [
                    'status' => $status,
                    'location' => $row['recievedby'] ?? $row['station'] ?? null,
                    'description' => filled($row['code'] ?? null) ? 'Code: '.$row['code'] : null,
                    'event_at' => Carbon::parse($datetime)->toDateTimeString(),
                ];
            })
            ->filter()
            ->sortBy('event_at')
            ->values()
            ->all();
    }
}
