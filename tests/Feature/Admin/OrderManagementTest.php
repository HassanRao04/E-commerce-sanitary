<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_search_and_filter_orders(): void
    {
        $order = Order::first();
        $order->update(['status' => 'processing']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['q' => $order->order_number]))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['status' => 'processing']))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_admin_can_update_order_status_and_notify_customer(): void
    {
        $order = Order::query()->whereNotNull('user_id')->first();

        if (! $order) {
            $customer = User::role('customer')->first();
            $order = Order::first();
            $order->update(['user_id' => $customer->id]);
        }

        $this->actingAs($this->admin)->patch(route('admin.orders.update-status', $order), [
            'status' => 'packed',
            'note' => 'Items packed and ready',
        ])->assertRedirect();

        $this->assertSame('packed', $order->fresh()->status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $order->user_id,
            'type' => 'order.status_updated',
        ]);
    }

    public function test_admin_can_view_order_tracking_page(): void
    {
        $order = Order::first();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.track', $order))
            ->assertOk()
            ->assertSee('Order Timeline');
    }

    public function test_admin_can_print_invoice(): void
    {
        $order = Order::first();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.invoice.print', $order))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->assertNotNull($order->fresh()->invoice);
    }

    public function test_admin_can_print_shipping_label(): void
    {
        $shipment = \App\Models\Shipping::first();

        if (! $shipment) {
            $order = Order::first();
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

    public function test_dashboard_shows_order_status_breakdown(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Orders by Status');
    }
}
