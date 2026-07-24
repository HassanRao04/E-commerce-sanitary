<?php

namespace Tests\Feature\Influencer;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerCommissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_commission_history_page_shows_order_based_rows(): void
    {
        $influencer = User::factory()->create(['email' => 'comm.influencer@example.com']);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'COMMHX',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        Order::factory()->create([
            'order_number' => 'ORD-COMM-1',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 82,
            'influencer_commission_paid_at' => null,
            'grand_total' => 4100,
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-COMM-2',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 40,
            'influencer_commission_paid_at' => now()->subDay(),
            'grand_total' => 2000,
        ]);

        $this->actingAs($influencer)
            ->get(route('influencer.commissions.index'))
            ->assertOk()
            ->assertSee('Commission History')
            ->assertSee('ORD-COMM-1')
            ->assertSee('ORD-COMM-2')
            ->assertSee('COMMHX')
            ->assertSee('Pending')
            ->assertSee('Paid')
            ->assertSee('CSV Export')
            ->assertSee('Excel Export');
    }

    public function test_commission_history_exports_csv_and_excel(): void
    {
        $influencer = User::factory()->create(['email' => 'export.comm@example.com']);
        $influencer->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'EXCOMM',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        Order::factory()->create([
            'order_number' => 'ORD-EX-1',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 15.5,
            'influencer_commission_paid_at' => null,
            'grand_total' => 775,
        ]);

        $csv = $this->actingAs($influencer)
            ->get(route('influencer.commissions.export', 'csv'));
        $csv->assertOk();
        $csv->assertHeader('content-disposition');
        $this->assertStringContainsString('ORD-EX-1', $csv->streamedContent());
        $this->assertStringContainsString('Pending', $csv->streamedContent());

        $excel = $this->actingAs($influencer)
            ->get(route('influencer.commissions.export', 'excel'));
        $excel->assertOk();
        $excel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_non_influencer_cannot_access_commission_history(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('influencer.commissions.index'))
            ->assertForbidden();
    }
}
