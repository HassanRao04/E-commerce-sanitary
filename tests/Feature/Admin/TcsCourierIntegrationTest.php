<?php

namespace Tests\Feature\Admin;

use App\Enums\ShipmentBookingStatus;
use App\Enums\TrackingEventSource;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TcsCourierIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
        Storage::fake('local');
    }

    public function test_tcs_booking_uses_api_when_credentials_configured(): void
    {
        $order = $this->orderWithoutShipment();
        $provider = $this->configuredTcsProvider();

        Http::fake([
            'devconnect.tcscourier.com/ecom/api/authentication/token*' => Http::response([
                'accesstoken' => 'test-token',
                'message' => 'SUCCESS',
                'status' => 'true',
            ]),
            'devconnect.tcscourier.com/ecom/api/booking/create' => Http::response([
                'consignmentNo' => '779412326902',
                'message' => 'SUCCESS',
                'status' => true,
            ]),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.shipping.book', $order), [
                'courier_provider_id' => $provider->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $shipment = $order->fresh()->shipments()->first();

        $this->assertNotNull($shipment);
        $this->assertSame('779412326902', $shipment->tracking_number);
        $this->assertSame(ShipmentBookingStatus::Booked, $shipment->booking_status);
        $this->assertFalse($shipment->booking_meta['simulated'] ?? true);
    }

    public function test_tcs_tracking_sync_imports_events(): void
    {
        $provider = $this->configuredTcsProvider();
        $order = $this->orderWithoutShipment();

        $shipment = Shipping::create([
            'order_id' => $order->id,
            'courier_provider_id' => $provider->id,
            'courier_name' => $provider->name,
            'tracking_number' => '779412326902',
            'external_shipment_id' => '779412326902',
            'status' => 'pending',
            'booking_status' => ShipmentBookingStatus::Booked,
            'booked_at' => now(),
        ]);

        Http::fake([
            'devconnect.tcscourier.com/ecom/api/authentication/token*' => Http::response([
                'accesstoken' => 'test-token',
            ]),
            'devconnect.tcscourier.com/tracking/api/Tracking/GetDynamicTrackDetail*' => Http::response([
                'checkpoints' => [
                    [
                        'consignmentno' => '779412326902',
                        'datetime' => 'Monday Oct 14, 2024 23:19',
                        'recievedby' => 'LAHORE',
                        'status' => 'Arrived at TCS Facility',
                    ],
                    [
                        'consignmentno' => '779412326902',
                        'datetime' => 'Thursday Oct 17, 2024 12:58',
                        'recievedby' => 'IMROOZ',
                        'status' => 'Shipment Delivered',
                    ],
                ],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.shipping.sync-tracking', $shipment))
            ->assertRedirect()
            ->assertSessionHas('success');

        $events = $shipment->fresh()->trackingEvents()->orderBy('event_at')->get();

        $this->assertCount(2, $events);
        $this->assertSame('Arrived at TCS Facility', $events->first()->status);
        $this->assertSame('Shipment Delivered', $events->last()->status);
        $this->assertSame(TrackingEventSource::Api, $events->first()->source);
    }

    public function test_tcs_courier_label_downloads_pdf(): void
    {
        $provider = $this->configuredTcsProvider();
        $order = $this->orderWithoutShipment();

        $shipment = Shipping::create([
            'order_id' => $order->id,
            'courier_provider_id' => $provider->id,
            'courier_name' => $provider->name,
            'tracking_number' => '779412326902',
            'external_shipment_id' => '779412326902',
            'status' => 'pending',
            'booking_status' => ShipmentBookingStatus::Booked,
            'booked_at' => now(),
        ]);

        Http::fake([
            'devconnect.tcscourier.com/ecom/api/authentication/token*' => Http::response([
                'accesstoken' => 'test-token',
            ]),
            'devconnect.tcscourier.com/ecom/api/print/label*' => Http::response('%PDF-1.4 fake label', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.courier-label', $shipment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertNotNull($shipment->fresh()->label_path);
        Storage::disk('local')->assertExists($shipment->fresh()->label_path);
    }

    private function configuredTcsProvider(): CourierProvider
    {
        $provider = CourierProvider::where('slug', 'tcs')->firstOrFail();
        $provider->update([
            'is_active' => true,
            'is_sandbox' => true,
            'api_base_url' => 'https://devconnect.tcscourier.com',
            'account_number' => '217698',
            'pickup_address' => 'Test Warehouse Lahore',
            'pickup_city' => 'Lahore',
            'credentials' => [
                'api_key' => 'tcs-user',
                'api_secret' => 'tcs-secret',
            ],
        ]);

        return $provider->fresh();
    }

    private function orderWithoutShipment(): Order
    {
        $order = Order::query()->doesntHave('shipments')->first();

        if ($order) {
            return $order;
        }

        $order = Order::firstOrFail();
        $order->shipments()->delete();

        return $order->fresh();
    }
}
