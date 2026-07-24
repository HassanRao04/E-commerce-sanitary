<?php

namespace Tests\Feature\Influencer;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_influencer_is_redirected_to_influencer_dashboard_after_login(): void
    {
        $influencer = User::factory()->create([
            'email' => 'dash.influencer@example.com',
            'password' => bcrypt('password'),
        ]);
        $influencer->assignRole('influencer');

        $this->post('/login', [
            'email' => $influencer->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('influencer.dashboard', absolute: false));

        $this->assertAuthenticatedAs($influencer);
    }

    public function test_influencer_dashboard_shows_metrics_from_orders(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Dash',
            'last_name' => 'Influencer',
            'name' => 'Dash Influencer',
            'email' => 'metrics.influencer@example.com',
        ]);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'DASH10',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        Order::factory()->create([
            'order_number' => 'ORD-DASH-1',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 82,
            'influencer_commission_paid_at' => null,
            'grand_total' => 4100,
            'created_at' => now(),
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-DASH-2',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 40,
            'influencer_commission_paid_at' => now()->subDay(),
            'grand_total' => 2000,
            'created_at' => now()->subDays(2),
        ]);

        $this->actingAs($influencer)
            ->get(route('influencer.dashboard'))
            ->assertOk()
            ->assertSee('Influencer Dashboard')
            ->assertSee('Commission')
            ->assertSee('Current Wallet Balance')
            ->assertSee('Pending Commission')
            ->assertSee('Paid Commission')
            ->assertSee('Lifetime Earnings')
            ->assertSee("Today's Earnings", false)
            ->assertSee('This Month Earnings')
            ->assertSee('Total Orders')
            ->assertSee('Total Sales')
            ->assertSee('Coupon Usage')
            ->assertSee('Latest Orders')
            ->assertSee('Latest Payouts')
            ->assertSee('DASH10')
            ->assertSee('ORD-DASH-1')
            ->assertSee('ORD-DASH-2')
            ->assertDontSee('Admin Notes');
    }

    public function test_influencer_dashboard_shows_own_payouts_only(): void
    {
        $influencer = User::factory()->create(['email' => 'payout.owner@example.com']);
        $influencer->assignRole('influencer');
        $other = User::factory()->create(['email' => 'payout.other@example.com']);
        $other->assignRole('influencer');

        $ownCoupon = Coupon::factory()->create([
            'code' => 'OWNPAY',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);
        $otherCoupon = Coupon::factory()->create([
            'code' => 'OTHPAY',
            'influencer_id' => $other->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $ownOrder = Order::factory()->create([
            'order_number' => 'ORD-OWN-PAY',
            'coupon_id' => $ownCoupon->id,
            'coupon_code' => $ownCoupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 50,
            'grand_total' => 2500,
        ]);
        $otherOrder = Order::factory()->create([
            'order_number' => 'ORD-OTH-PAY',
            'coupon_id' => $otherCoupon->id,
            'coupon_code' => $otherCoupon->code,
            'influencer_id' => $other->id,
            'influencer_commission_amount' => 99,
            'grand_total' => 4950,
        ]);

        $service = app(CouponService::class);
        $service->recordCommissionCredit($ownOrder);
        $service->recordCommissionCredit($otherOrder);
        $service->payOrderCommission($ownOrder, 50, 'Secret admin note for owner', null);
        $service->payOrderCommission($otherOrder, 99, 'Secret admin note for other', null);

        $this->actingAs($influencer)
            ->get(route('influencer.dashboard'))
            ->assertOk()
            ->assertSee('ORD-OWN-PAY')
            ->assertDontSee('ORD-OTH-PAY')
            ->assertDontSee('Secret admin note')
            ->assertDontSee('OTHPAY');
    }

    public function test_non_influencer_cannot_access_influencer_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('influencer.dashboard'))
            ->assertForbidden();
    }
}
