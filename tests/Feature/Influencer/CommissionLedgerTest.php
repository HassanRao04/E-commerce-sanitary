<?php

namespace Tests\Feature\Influencer;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\InfluencerCommissionTransaction;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_ledger_credits_and_manual_payout_running_balance(): void
    {
        $influencer = User::factory()->create(['email' => 'ledger.influencer@example.com']);
        $influencer->assignRole('influencer');
        $admin = User::where('email', config('shop.admin_email'))->first();

        $coupon = Coupon::factory()->create([
            'code' => 'LEDGER1',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        $orderA = Order::factory()->create([
            'order_number' => 'ORD-LEDGER-1001',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 20,
            'grand_total' => 1000,
        ]);
        $orderB = Order::factory()->create([
            'order_number' => 'ORD-LEDGER-1002',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 30,
            'grand_total' => 1500,
        ]);

        $service = app(CouponService::class);
        $service->recordCommissionCredit($orderA);
        $service->recordCommissionCredit($orderB);

        $this->assertEquals(50.0, $service->influencerWallet($influencer)['balance']);

        $this->actingAs($admin)
            ->from(route('admin.influencer-performance.show', $influencer))
            ->post(route('admin.influencer-performance.payout', $influencer), [
                'amount' => 50,
                'admin_notes' => 'Manual payout',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $wallet = $service->influencerWallet($influencer);
        $this->assertEquals(0.0, $wallet['balance']);
        $this->assertEquals(50.0, $wallet['debits_total']);

        $ledger = $service->influencerLedger($influencer);
        $this->assertCount(3, $ledger);
        $this->assertEquals(20.0, $ledger[0]->credit);
        $this->assertEquals(20.0, $ledger[0]->running_balance);
        $this->assertEquals(30.0, $ledger[1]->credit);
        $this->assertEquals(50.0, $ledger[1]->running_balance);
        $this->assertEquals(50.0, $ledger[2]->debit);
        $this->assertEquals(0.0, $ledger[2]->running_balance);
        $this->assertEquals('Manual payout', $ledger[2]->admin_notes);

        // Credit amount still only on order — not copied onto transaction.amount
        $creditRow = InfluencerCommissionTransaction::query()->where('order_id', $orderA->id)->first();
        $this->assertNull($creditRow->amount);
        $this->assertEquals(20.0, (float) $orderA->fresh()->influencer_commission_amount);
    }

    public function test_track_influencer_order_creates_ledger_credit(): void
    {
        $influencer = User::factory()->create(['email' => 'track.ledger@example.com']);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'TRACKLED',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 5,
        ]);

        $order = Order::factory()->create([
            'grand_total' => 200,
            'influencer_id' => null,
            'influencer_commission_amount' => 0,
        ]);

        app(CouponService::class)->trackInfluencerOrder($order, $coupon);

        $order->refresh();
        $this->assertEquals($influencer->id, $order->influencer_id);
        $this->assertEquals(10.0, (float) $order->influencer_commission_amount);
        $this->assertDatabaseHas('influencer_commission_transactions', [
            'order_id' => $order->id,
            'influencer_id' => $influencer->id,
            'type' => 'credit',
        ]);
    }
}
