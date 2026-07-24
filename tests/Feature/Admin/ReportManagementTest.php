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
            ->assertSee('Influencer Reports')
            ->assertSee('Daily Sales')
            ->assertSee('Top Influencers')
            ->assertSee('Highest Commission')
            ->assertSee('Pending Payout')
            ->assertSee('Paid Payout')
            ->assertSee('Coupon Usage')
            ->assertSee('Monthly Commission')
            ->assertSee('Yearly Commission')
            ->assertSee('Shipment Status');
    }

    public function test_admin_can_view_influencer_top_report(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Report',
            'last_name' => 'Star',
            'name' => 'Report Star',
            'email' => 'report.star@example.com',
        ]);
        $influencer->assignRole('influencer');

        Order::factory()->paid()->create([
            'influencer_id' => $influencer->id,
            'coupon_code' => 'REP10',
            'grand_total' => 5000,
            'influencer_commission_amount' => 250,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'influencer-top'))
            ->assertOk()
            ->assertSee('Top Influencers')
            ->assertSee('Report Star')
            ->assertSee('Detailed Data')
            ->assertSee('CSV')
            ->assertSee('Excel');
    }

    public function test_admin_can_view_pending_and_paid_payout_reports(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Payout',
            'last_name' => 'Rep',
            'name' => 'Payout Rep',
            'email' => 'payout.rep@example.com',
        ]);
        $influencer->assignRole('influencer');

        Order::factory()->paid()->create([
            'order_number' => 'ORD-PEND-REP',
            'influencer_id' => $influencer->id,
            'coupon_code' => 'PEND10',
            'grand_total' => 2000,
            'influencer_commission_amount' => 100,
            'influencer_commission_paid_at' => null,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $paidOrder = Order::factory()->paid()->create([
            'order_number' => 'ORD-PAID-REP',
            'influencer_id' => $influencer->id,
            'coupon_code' => 'PAID10',
            'grand_total' => 3000,
            'influencer_commission_amount' => 150,
            'influencer_commission_paid_at' => null,
            'payment_status' => PaymentStatus::Paid,
        ]);

        app(\App\Services\CouponService::class)->payOrderCommission(
            $paidOrder,
            150,
            'Report payout note',
            $this->admin,
            'TXN-REP-1',
        );

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'influencer-pending-payout'))
            ->assertOk()
            ->assertSee('Pending Payout')
            ->assertSee('ORD-PEND-REP')
            ->assertSee('Payout Rep')
            ->assertDontSee('ORD-PAID-REP');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'influencer-paid-payout'))
            ->assertOk()
            ->assertSee('Paid Payout')
            ->assertSee('ORD-PAID-REP')
            ->assertSee('Report payout note')
            ->assertSee('TXN-REP-1');
    }

    public function test_admin_can_view_monthly_commission_report_and_export_csv_excel(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Month',
            'last_name' => 'Comm',
            'email' => 'month.comm@example.com',
        ]);
        $influencer->assignRole('influencer');

        Order::factory()->paid()->create([
            'influencer_id' => $influencer->id,
            'grand_total' => 4000,
            'influencer_commission_amount' => 200,
            'payment_status' => PaymentStatus::Paid,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'influencer-monthly-commission'))
            ->assertOk()
            ->assertSee('Monthly Commission')
            ->assertSee('Detailed Data');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', 'influencer-yearly-commission'))
            ->assertOk()
            ->assertSee('Yearly Commission');

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.reports.export', ['influencer-monthly-commission', 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));

        $excel = $this->actingAs($this->admin)
            ->get(route('admin.reports.export', ['influencer-paid-payout', 'excel']));
        $excel->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $excel->headers->get('content-type')
        );
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
