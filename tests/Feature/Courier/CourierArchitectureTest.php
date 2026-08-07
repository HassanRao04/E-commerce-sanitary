<?php

namespace Tests\Feature\Courier;

use App\Enums\ShipmentBookingStatus;
use App\Enums\TrackingEventSource;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use App\Services\Couriers\CourierIntegrationService;
use App\Services\Couriers\CourierProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_courier_provider_registry_is_seeded(): void
    {
        $this->assertDatabaseHas('courier_providers', [
            'slug' => 'manual',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('courier_providers', [
            'slug' => 'tcs',
            'is_active' => false,
        ]);
    }

    public function test_manual_provider_is_always_enabled(): void
    {
        $manager = app(CourierProviderManager::class);

        $this->assertTrue($manager->resolve('manual')->isEnabled());
        $this->assertContains('manual', array_map(fn ($provider) => $provider->slug(), $manager->enabled()));
    }

    public function test_shipment_relationships_include_courier_provider(): void
    {
        $provider = CourierProvider::where('slug', 'tcs')->firstOrFail();
        $order = Order::firstOrFail();

        $shipment = Shipping::create([
            'order_id' => $order->id,
            'courier_provider_id' => $provider->id,
            'courier_name' => 'TCS',
            'tracking_number' => 'TCS999888',
            'status' => 'pending',
            'booking_status' => ShipmentBookingStatus::Manual,
        ]);

        $shipment->load('courierProvider');

        $this->assertSame($provider->id, $shipment->courierProvider->id);
        $this->assertSame(
            'https://www.tcsexpress.com/track/TCS999888',
            $shipment->tracking_url,
        );
    }

    public function test_manual_shipment_creation_keeps_existing_workflow(): void
    {
        $order = Order::firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.orders.shipping.store', $order), [
            'courier_name' => 'TCS',
            'tracking_number' => 'TCS123456',
            'status' => 'pending',
        ])->assertRedirect();

        $shipment = $order->fresh()->shipments()->first();

        $this->assertNotNull($shipment);
        $this->assertSame('TCS', $shipment->courier_name);
        $this->assertSame(ShipmentBookingStatus::Manual, $shipment->booking_status);
    }

    public function test_tracking_events_default_to_manual_source(): void
    {
        $shipment = Shipping::firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.shipping.events.store', $shipment), [
            'status' => 'In Transit',
            'location' => 'Lahore Hub',
            'description' => 'Package scanned',
            'event_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $event = $shipment->fresh()->trackingEvents()->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame(TrackingEventSource::Manual, $event->source);
    }

    public function test_courier_integration_service_exposes_booking_status_default(): void
    {
        $service = app(CourierIntegrationService::class);

        $this->assertSame(ShipmentBookingStatus::Manual, $service->defaultBookingStatus());
    }

    public function test_existing_print_label_flow_still_works(): void
    {
        $shipment = Shipping::first();

        if (! $shipment) {
            $order = Order::firstOrFail();
            $this->actingAs($this->admin)->post(route('admin.orders.shipping.store', $order), [
                'courier_name' => 'TCS',
                'tracking_number' => 'TCS123456',
                'status' => 'pending',
            ]);
            $shipment = $order->fresh()->shipments()->first();
        }

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', $shipment))
            ->assertOk()
            ->assertSee($shipment->courier_name);
    }
}
