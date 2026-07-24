<?php

namespace Tests\Feature\Admin;

use App\Enums\CouponType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_influencer_performance_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.influencer-performance.index'))
            ->assertOk()
            ->assertSee('Influencer Performance');
    }

    public function test_influencer_performance_aggregates_order_tracking_data(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Muhammad',
            'last_name' => 'Hassan',
            'name' => 'Muhammad Hassan',
            'email' => 'perf.influencer@example.com',
        ]);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'PERF10',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 10,
        ]);

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        Order::factory()->create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'subtotal' => 10000,
            'discount_total' => 1000,
            'grand_total' => 9000,
            'coupon_code' => $coupon->code,
            'coupon_id' => $coupon->id,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 900,
            'influencer_commission_paid_at' => null,
        ]);

        Order::factory()->create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Paid,
            'subtotal' => 5000,
            'discount_total' => 500,
            'grand_total' => 4500,
            'coupon_code' => $coupon->code,
            'coupon_id' => $coupon->id,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 450,
            'influencer_commission_paid_at' => now(),
        ]);

        $row = app(\App\Services\CouponService::class)->influencerPerformance()->first();
        $this->assertNotNull($row);
        $this->assertSame('Muhammad Hassan', $row->influencer_name);
        $this->assertSame('PERF10', $row->coupon_code);
        $this->assertSame(2, $row->total_orders);
        $this->assertEquals(13500.0, $row->total_sales);
        $this->assertEquals(1500.0, $row->total_discount);
        $this->assertEquals(1350.0, $row->total_commission);
        $this->assertEquals(900.0, $row->pending_commission);
        $this->assertEquals(450.0, $row->paid_commission);

        $this->actingAs($this->admin)
            ->get(route('admin.influencer-performance.index'))
            ->assertOk()
            ->assertSee('Muhammad Hassan')
            ->assertSee('PERF10');
    }

    public function test_influencer_detail_page_shows_profile_orders_and_exports(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Detail',
            'last_name' => 'Influencer',
            'name' => 'Detail Influencer',
            'email' => 'detail.influencer@example.com',
        ]);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'DETAIL10',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 5,
        ]);

        $customer = User::factory()->create([
            'first_name' => 'Buyer',
            'last_name' => 'One',
            'name' => 'Buyer One',
            'email' => 'buyer.one@example.com',
        ]);
        $customer->assignRole('customer');

        Order::factory()->create([
            'user_id' => $customer->id,
            'customer_name' => 'Buyer One',
            'customer_email' => 'buyer.one@example.com',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'status' => 'pending',
            'subtotal' => 2000,
            'discount_total' => 200,
            'grand_total' => 1800,
            'coupon_code' => $coupon->code,
            'coupon_id' => $coupon->id,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 90,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.influencer-performance.show', $influencer))
            ->assertOk()
            ->assertSee('Detail Influencer')
            ->assertSee('Profile')
            ->assertSee('Coupons')
            ->assertSee('DETAIL10')
            ->assertSee('Orders')
            ->assertSee('Customers')
            ->assertSee('Buyer One')
            ->assertSee('CSV')
            ->assertSee('Excel');

        $this->actingAs($this->admin)
            ->get(route('admin.influencer-performance.export', [$influencer, 'csv']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($this->admin)
            ->get(route('admin.influencer-performance.export', [$influencer, 'excel']))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_admin_can_mark_pending_commission_as_paid(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Paid',
            'last_name' => 'Mark',
            'name' => 'Paid Mark',
            'email' => 'paid.mark@example.com',
        ]);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'MARKPAID',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $order = Order::factory()->create([
            'coupon_code' => $coupon->code,
            'coupon_id' => $coupon->id,
            'influencer_id' => $influencer->id,
            'grand_total' => 4100,
            'discount_total' => 0,
            'influencer_commission_amount' => 82,
            'influencer_commission_paid_at' => null,
        ]);

        $before = app(\App\Services\CouponService::class)->influencerSummary($influencer);
        $this->assertEquals(82.0, $before['pending_commission']);
        $this->assertEquals(0.0, $before['paid_commission']);

        $this->actingAs($this->admin)
            ->from(route('admin.influencer-performance.show', $influencer))
            ->patch(route('admin.influencer-performance.mark-paid', [$influencer, $order]))
            ->assertRedirect();

        $order->refresh();
        $this->assertNotNull($order->influencer_commission_paid_at);
        $this->assertEquals(82.0, (float) $order->influencer_commission_amount);
        $this->assertEquals(4100.0, (float) $order->grand_total);

        $after = app(\App\Services\CouponService::class)->influencerSummary($influencer);
        $this->assertEquals(0.0, $after['pending_commission']);
        $this->assertEquals(82.0, $after['paid_commission']);
        $this->assertEquals(82.0, $after['total_commission']);
    }

    public function test_admin_can_bulk_mark_pending_commissions_as_paid(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Bulk',
            'last_name' => 'Pay',
            'name' => 'Bulk Pay',
            'email' => 'bulk.pay@example.com',
        ]);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'BULKPAID',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $pendingA = Order::factory()->create([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 82,
            'influencer_commission_paid_at' => null,
            'grand_total' => 4100,
        ]);
        $pendingB = Order::factory()->create([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 50,
            'influencer_commission_paid_at' => null,
            'grand_total' => 2500,
        ]);
        $alreadyPaid = Order::factory()->create([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 40,
            'influencer_commission_paid_at' => now()->subDay(),
            'grand_total' => 2000,
        ]);
        $paidAt = $alreadyPaid->influencer_commission_paid_at->copy();

        $this->actingAs($this->admin)
            ->from(route('admin.influencer-performance.show', $influencer))
            ->post(route('admin.influencer-performance.mark-selected-paid', $influencer), [
                'order_ids' => [$pendingA->id, $pendingB->id, $alreadyPaid->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($pendingA->fresh()->influencer_commission_paid_at);
        $this->assertNotNull($pendingB->fresh()->influencer_commission_paid_at);
        $this->assertTrue(
            $alreadyPaid->fresh()->influencer_commission_paid_at->equalTo($paidAt),
            'Already paid commission timestamp must not change'
        );
        $this->assertEquals(82.0, (float) $pendingA->fresh()->influencer_commission_amount);
        $this->assertEquals(50.0, (float) $pendingB->fresh()->influencer_commission_amount);
        $this->assertEquals(40.0, (float) $alreadyPaid->fresh()->influencer_commission_amount);

        $summary = app(\App\Services\CouponService::class)->influencerSummary($influencer);
        $this->assertEquals(0.0, $summary['pending_commission']);
        $this->assertEquals(172.0, $summary['paid_commission']);
    }

    public function test_admin_can_pay_commission_with_ledger_debit_and_mark_paid(): void
    {
        $influencer = User::factory()->create([
            'name' => 'Pay Flow',
            'email' => 'pay.flow@example.com',
        ]);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'PAYFLOW',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $order = Order::factory()->create([
            'order_number' => 'ORD-PAY-1',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 20,
            'influencer_commission_paid_at' => null,
            'grand_total' => 1000,
        ]);

        $service = app(\App\Services\CouponService::class);
        $service->recordCommissionCredit($order);

        $before = $service->influencerWallet($influencer);
        $this->assertEquals(20.0, $before['balance']);

        $this->actingAs($this->admin)
            ->from(route('admin.influencer-performance.show', $influencer))
            ->post(route('admin.influencer-performance.pay-commission', [$influencer, $order]), [
                'amount' => 20,
                'admin_notes' => 'Paid via bank transfer',
            ])
            ->assertRedirect(route('admin.influencer-performance.show', $influencer))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertNotNull($order->influencer_commission_paid_at);
        $this->assertEquals(20.0, (float) $order->influencer_commission_amount);

        $after = $service->influencerWallet($influencer);
        $this->assertEquals(0.0, $after['balance']);
        $this->assertEquals(20.0, $after['debits_total']);
        $this->assertEquals(0.0, $after['pending']);
        $this->assertEquals(20.0, $after['paid']);

        $this->assertDatabaseHas('influencer_commission_transactions', [
            'influencer_id' => $influencer->id,
            'type' => 'debit',
            'reference_order_id' => $order->id,
            'amount' => 20,
            'admin_notes' => 'Paid via bank transfer',
        ]);
    }
}
