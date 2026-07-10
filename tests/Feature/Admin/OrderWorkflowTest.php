<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_order_workflow_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.orders.workflow.index'))
            ->assertOk()
            ->assertSee('Order Workflow')
            ->assertSee('Pending')
            ->assertSee('Delivered');
    }

    public function test_admin_can_create_custom_order_status(): void
    {
        $this->actingAs($this->admin)->post(route('admin.orders.workflow.store'), [
            'name' => 'Awaiting Pickup',
            'slug' => 'awaiting-pickup',
            'badge_color' => 'teal',
            'sort_order' => 45,
            'customer_group' => 'processing',
            'show_in_progress' => true,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('order_statuses', [
            'slug' => 'awaiting-pickup',
            'name' => 'Awaiting Pickup',
            'show_in_progress' => true,
        ]);
    }

    public function test_storefront_order_progress_uses_erp_status_names(): void
    {
        OrderStatus::query()->create([
            'slug' => 'quality_check',
            'name' => 'Quality Check',
            'badge_color' => 'sky',
            'sort_order' => 35,
            'show_in_progress' => true,
            'customer_group' => 'processing',
            'is_active' => true,
        ]);

        app(\App\Services\OrderWorkflowService::class)->clearCache();

        $order = Order::first();
        $order->update(['status' => 'quality_check']);

        $this->actingAs($order->user)
            ->get(route('shop.account.orders.show', $order))
            ->assertOk()
            ->assertSee('Quality Check');
    }

    public function test_system_status_cannot_be_deleted(): void
    {
        $pending = OrderStatus::query()->where('slug', 'pending')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('admin.orders.workflow.destroy', $pending))
            ->assertRedirect();

        $this->assertDatabaseHas('order_statuses', ['slug' => 'pending']);
    }
}
