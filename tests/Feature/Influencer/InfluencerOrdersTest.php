<?php

namespace Tests\Feature\Influencer;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_influencer_orders_page_shows_only_own_orders(): void
    {
        $influencer = User::factory()->create(['email' => 'orders.influencer@example.com']);
        $influencer->assignRole('influencer');
        $other = User::factory()->create(['email' => 'other.influencer@example.com']);
        $other->assignRole('influencer');

        $coupon = Coupon::factory()->create([
            'code' => 'OWNORD',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);
        $otherCoupon = Coupon::factory()->create([
            'code' => 'OTHORD',
            'influencer_id' => $other->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);

        Order::factory()->create([
            'order_number' => 'ORD-OWN-1',
            'customer_name' => 'Ali Khan',
            'customer_email' => 'ali@example.com',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 82,
            'influencer_commission_paid_at' => null,
            'grand_total' => 4100,
            'status' => 'pending',
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-OTHER-9',
            'coupon_id' => $otherCoupon->id,
            'coupon_code' => $otherCoupon->code,
            'influencer_id' => $other->id,
            'influencer_commission_amount' => 50,
            'grand_total' => 2500,
            'status' => 'pending',
        ]);

        $this->actingAs($influencer)
            ->get(route('influencer.orders.index'))
            ->assertOk()
            ->assertSee('ORD-OWN-1')
            ->assertSee('OWNORD')
            ->assertSee('Pending')
            ->assertDontSee('Customer')
            ->assertDontSee('Ali Khan')
            ->assertDontSee('ali@example.com')
            ->assertDontSee('ORD-OTHER-9');
    }

    public function test_influencer_orders_filters_by_search_and_coupon(): void
    {
        $influencer = User::factory()->create(['email' => 'filter.influencer@example.com']);
        $influencer->assignRole('influencer');

        $couponA = Coupon::factory()->create([
            'code' => 'FILTA',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 2,
        ]);
        $couponB = Coupon::factory()->create([
            'code' => 'FILTB',
            'influencer_id' => $influencer->id,
            'commission_enabled' => true,
            'commission_type' => CouponType::Percent,
            'commission_value' => 5,
        ]);

        Order::factory()->create([
            'order_number' => 'ORD-FILTER-A',
            'customer_name' => 'Sara Ahmed',
            'coupon_id' => $couponA->id,
            'coupon_code' => $couponA->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 10,
            'grand_total' => 500,
            'status' => 'pending',
        ]);
        Order::factory()->create([
            'order_number' => 'ORD-FILTER-B',
            'customer_name' => 'Other Buyer',
            'coupon_id' => $couponB->id,
            'coupon_code' => $couponB->code,
            'influencer_id' => $influencer->id,
            'influencer_commission_amount' => 20,
            'grand_total' => 400,
            'status' => 'delivered',
        ]);

        $this->actingAs($influencer)
            ->get(route('influencer.orders.index', [
                'search' => 'Sara',
                'coupon_id' => $couponA->id,
            ]))
            ->assertOk()
            ->assertSee('ORD-FILTER-A')
            ->assertDontSee('ORD-FILTER-B');
    }

    public function test_non_influencer_cannot_access_orders_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('influencer.orders.index'))
            ->assertForbidden();
    }
}
