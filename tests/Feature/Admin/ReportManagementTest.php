<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_erp_reporting_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reporting Dashboard')
            ->assertSee('Sales Reports')
            ->assertSee('Product Reports')
            ->assertSee('Inventory Reports')
            ->assertSee('Shipping Reports')
            ->assertSee('Customer Reports')
            ->assertSee('Daily Sales')
            ->assertSee('Shipment Status');
    }

    public function test_admin_can_view_daily_sales_report(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'daily-sales'))
            ->assertOk()
            ->assertSee('Daily Sales')
            ->assertSee('Detailed Data');
    }

    public function test_admin_can_view_shipping_status_report_from_erp_transactions(): void
    {
        $order = Order::factory()->paid()->create([
            'shipping_total' => 750,
            'payment_status' => PaymentStatus::Paid,
        ]);

        Shipping::query()->create([
            'order_id' => $order->id,
            'courier_name' => 'TCS',
            'tracking_number' => 'TCS123456',
            'status' => ShipmentStatus::Delivered,
            'shipped_at' => now()->subDays(3),
            'delivered_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'shipping-status'))
            ->assertOk()
            ->assertSee('Shipment Status')
            ->assertSee('Delivered')
            ->assertSee('Detailed Data');
    }

    public function test_admin_can_export_daily_sales_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.export', ['daily-sales', 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }

    public function test_admin_can_export_product_sales_excel(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.export', ['product-sales', 'excel']));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_admin_can_export_shipping_courier_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.export', ['shipping-courier', 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_admin_can_export_revenue_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.export', ['revenue', 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_guest_cannot_access_reports(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect();
    }

    public function test_invalid_report_type_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'invalid-report'))
            ->assertNotFound();
    }
}
