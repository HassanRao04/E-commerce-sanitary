<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_orders(): void
    {
        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk();
    }

    public function test_admin_can_view_order_detail(): void
    {
        $order = Order::first();
        $this->assertNotNull($order);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_admin_can_update_order_status(): void
    {
        $order = Order::first();
        $order->update(['status' => OrderStatus::Pending]);

        $this->actingAs($this->admin)->patch(route('admin.orders.update-status', $order), [
            'status' => OrderStatus::Processing->value,
            'note' => 'Processing started',
        ])->assertRedirect();

        $this->assertEquals(OrderStatus::Processing, $order->fresh()->status);
    }

    public function test_admin_can_view_customers_and_invoices(): void
    {
        $this->actingAs($this->admin)->get(route('admin.customers.index'))->assertOk();

        $customer = Customer::first();
        $this->actingAs($this->admin)->get(route('admin.customers.show', $customer))->assertOk();

        $this->actingAs($this->admin)->get(route('admin.invoices.index'))->assertOk();
    }

    public function test_admin_can_generate_and_issue_invoice(): void
    {
        $order = Order::first();

        $this->actingAs($this->admin)->post(route('admin.orders.invoice.generate', $order))->assertRedirect();

        $invoice = $order->fresh()->invoice;
        $this->assertNotNull($invoice);

        $this->actingAs($this->admin)->patch(route('admin.invoices.issue', $invoice))->assertRedirect();

        $this->assertEquals(InvoiceStatus::Issued, $invoice->fresh()->status);
    }

    public function test_admin_can_view_shipping(): void
    {
        $this->actingAs($this->admin)->get(route('admin.shipping.index'))->assertOk();

        $shipment = Shipping::first();
        if ($shipment) {
            $this->actingAs($this->admin)->get(route('admin.shipping.show', $shipment))->assertOk();
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_seeded_orders_exist(): void
    {
        $this->assertTrue(Order::query()->count() >= 3);
        $this->assertTrue(Customer::query()->count() >= 3);
    }
}
