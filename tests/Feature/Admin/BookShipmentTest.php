<?php

namespace Tests\Feature\Admin;

use App\Enums\ShipmentBookingStatus;
use App\Enums\TrackingEventSource;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShipmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_order_details_shows_book_shipment_form(): void
    {
        $order = $this->orderWithoutShipment();
        CourierProvider::where('slug', 'tcs')->update(['is_active' => true]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Book Shipment')
            ->assertSee('Create Shipment Manually')
            ->assertSee('TCS');
    }

    public function test_admin_can_simulate_book_shipment(): void
    {
        $order = $this->orderWithoutShipment();
        $provider = CourierProvider::where('slug', 'tcs')->firstOrFail();
        $provider->update(['is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.shipping.book', $order), [
                'courier_provider_id' => $provider->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $shipment = $order->fresh()->shipments()->first();

        $this->assertNotNull($shipment);
        $this->assertSame($provider->id, $shipment->courier_provider_id);
        $this->assertSame('TCS', $shipment->courier_name);
        $this->assertSame(ShipmentBookingStatus::Booked, $shipment->booking_status);
        $this->assertNotNull($shipment->tracking_number);
        $this->assertNotNull($shipment->external_shipment_id);
        $this->assertNotNull($shipment->awb_number);
        $this->assertNotNull($shipment->booked_at);
        $this->assertTrue($shipment->booking_meta['simulated'] ?? false);

        $event = $shipment->trackingEvents()->first();
        $this->assertNotNull($event);
        $this->assertSame(TrackingEventSource::Api, $event->source);
    }

    public function test_manual_shipment_creation_still_works(): void
    {
        $order = $this->orderWithoutShipment();

        $this->actingAs($this->admin)
            ->post(route('admin.orders.shipping.store', $order), [
                'courier_name' => 'TCS',
                'tracking_number' => 'TCS123456',
                'status' => 'pending',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $shipment = $order->fresh()->shipments()->first();

        $this->assertNotNull($shipment);
        $this->assertSame(ShipmentBookingStatus::Manual, $shipment->booking_status);
        $this->assertSame('TCS123456', $shipment->tracking_number);
    }

    public function test_cannot_book_shipment_twice(): void
    {
        $order = $this->orderWithoutShipment();
        $provider = CourierProvider::where('slug', 'tcs')->firstOrFail();
        $provider->update(['is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.orders.shipping.book', $order), [
            'courier_provider_id' => $provider->id,
        ])->assertRedirect();

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.shipping.book', $order), [
                'courier_provider_id' => $provider->id,
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('courier_provider_id');

        $this->assertSame(1, $order->fresh()->shipments()->count());
    }

    public function test_booked_order_hides_book_form(): void
    {
        $order = $this->orderWithoutShipment();
        $provider = CourierProvider::where('slug', 'tcs')->firstOrFail();
        $provider->update(['is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.orders.shipping.book', $order), [
            'courier_provider_id' => $provider->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->assertDontSee('Book Shipment')
            ->assertSee($order->fresh()->shipments()->first()->tracking_number);
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
