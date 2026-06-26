<?php

namespace Tests\Feature\Storefront;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_customer_dashboard_shows_order_stats(): void
    {
        $customer = User::query()->role('customer')->first();

        Order::factory()->count(2)->create(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('shop.account.dashboard'))
            ->assertOk()
            ->assertSee('Total orders')
            ->assertSee('Total amount spent')
            ->assertSee('Recent orders');
    }

    public function test_customer_orders_page_lists_orders(): void
    {
        $customer = User::query()->role('customer')->first();
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('shop.account.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Invoice');
    }

    public function test_customer_can_view_order_details(): void
    {
        $customer = User::query()->role('customer')->first();
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('shop.account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Ordered products')
            ->assertSee('Order progress');
    }

    public function test_customer_addresses_page_is_accessible(): void
    {
        $customer = User::query()->role('customer')->first();

        $this->actingAs($customer)
            ->get(route('shop.account.addresses.index'))
            ->assertOk()
            ->assertSee('My addresses');
    }
}
