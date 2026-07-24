<?php

namespace Tests\Feature\Influencer;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_wallet_balance_is_sum_of_order_commissions(): void
    {
        $influencer = User::factory()->create(['email' => 'wallet.influencer@example.com']);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'WLT20',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        Order::factory()->create([
            'order_number' => 'ORD-WLT-1',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 20,
            'grand_total' => 1000,
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-WLT-2',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 40,
            'grand_total' => 2000,
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-WLT-3',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 15,
            'grand_total' => 750,
        ]);

        $service = app(CouponService::class);
        foreach (Order::query()->where('influencer_id', $influencer->id)->get() as $order) {
            $service->recordCommissionCredit($order);
        }

        $wallet = $service->influencerWallet($influencer);

        $this->assertEquals(75.0, $wallet['balance']);
        $this->assertEquals(3, $wallet['credits_count']);

        $this->actingAs($influencer)
            ->get(route('influencer.wallet'))
            ->assertOk()
            ->assertSee('Wallet')
            ->assertSee('Payout History')
            ->assertSee('Commission ledger')
            ->assertSee('ORD-WLT-1')
            ->assertSee('ORD-WLT-2')
            ->assertSee('ORD-WLT-3');

        $this->actingAs($influencer)
            ->get(route('influencer.dashboard'))
            ->assertOk()
            ->assertSee('Wallet Balance');
    }

    public function test_mark_paid_does_not_change_wallet_balance(): void
    {
        $influencer = User::factory()->create(['email' => 'wallet.paid@example.com']);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'WLTPAY',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $order = Order::factory()->create([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 20,
            'influencer_commission_paid_at' => null,
            'grand_total' => 1000,
        ]);

        $service = app(CouponService::class);
        $before = $service->influencerWallet($influencer);
        $this->assertEquals(20.0, $before['balance']);
        $this->assertEquals(20.0, $before['pending']);
        $this->assertEquals(0.0, $before['paid']);

        $service->markCommissionPaid($order);

        $after = $service->influencerWallet($influencer);
        $this->assertEquals(20.0, $after['balance']);
        $this->assertEquals(0.0, $after['pending']);
        $this->assertEquals(20.0, $after['paid']);
    }

    public function test_wallet_payout_history_shows_debits_only_with_details(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Payout',
            'last_name' => 'Admin',
            'name' => 'Payout Admin',
            'email' => 'payout.admin@example.com',
        ]);
        $admin->assignRole('admin');

        $influencer = User::factory()->create(['email' => 'payout.history@example.com']);
        $influencer->assignRole('influencer');
        $other = User::factory()->create(['email' => 'payout.history.other@example.com']);
        $other->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'PHIST',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);
        $otherCoupon = Coupon::factory()->create([
            'code' => 'PHOTH',
            'influencer_id' => $other->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $order = Order::factory()->create([
            'order_number' => 'ORD-PHIST-1',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 50,
            'grand_total' => 2500,
        ]);
        $creditOnly = Order::factory()->create([
            'order_number' => 'ORD-PHIST-CREDIT',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 25,
            'grand_total' => 1250,
        ]);
        $otherOrder = Order::factory()->create([
            'order_number' => 'ORD-PHIST-OTHER',
            'coupon_id' => $otherCoupon->id,
            'coupon_code' => $otherCoupon->code,
            'influencer_id' => $other->id,
            'influencer_commission_amount' => 80,
            'grand_total' => 4000,
        ]);

        $service = app(CouponService::class);
        $service->recordCommissionCredit($order);
        $service->recordCommissionCredit($creditOnly);
        $service->recordCommissionCredit($otherOrder);
        $service->payOrderCommission($order, 50, 'Bank transfer note', $admin, 'TXN-PHIST-99');
        $service->payOrderCommission($otherOrder, 80, 'Other influencer note', $admin, 'TXN-OTHER');

        $history = $service->influencerPayoutHistory($influencer);
        $this->assertCount(1, $history);
        $this->assertEquals(50.0, $history[0]->amount);
        $this->assertEquals('ORD-PHIST-1', $history[0]->reference);
        $this->assertEquals('Payout Admin', $history[0]->admin);
        $this->assertEquals('Bank transfer note', $history[0]->payment_note);
        $this->assertEquals('TXN-PHIST-99', $history[0]->transaction_id);

        $this->actingAs($influencer)
            ->get(route('influencer.wallet'))
            ->assertOk()
            ->assertSee('Payout History')
            ->assertSee('Date')
            ->assertSee('Amount')
            ->assertSee('Reference')
            ->assertSee('Admin')
            ->assertSee('Status')
            ->assertSee('Payment Note')
            ->assertSee('Transaction ID')
            ->assertSee('ORD-PHIST-1')
            ->assertSee('Payout Admin')
            ->assertSee('Bank transfer note')
            ->assertSee('TXN-PHIST-99')
            ->assertDontSee('ORD-PHIST-OTHER')
            ->assertDontSee('Other influencer note')
            ->assertDontSee('TXN-OTHER');
    }
}
