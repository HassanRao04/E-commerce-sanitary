<?php

namespace Tests\Feature\Admin;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_create_coupon(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'TESTSAVE',
                'type' => CouponType::Percent->value,
                'value' => 20,
                'min_order_amount' => 1000,
                'max_uses' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'TESTSAVE',
            'type' => CouponType::Percent->value,
        ]);
    }

    public function test_admin_can_update_coupon(): void
    {
        $coupon = Coupon::where('code', 'WELCOME10')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'WELCOME10',
                'type' => CouponType::Percent->value,
                'value' => 12,
                'min_order_amount' => 5000,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertEquals(12, (float) $coupon->fresh()->value);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'DELETEME']);

        $this->actingAs($this->admin)
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertSoftDeleted('coupons', ['code' => 'DELETEME']);
    }

    public function test_coupon_form_is_available(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertSee('Create coupon');
    }
}
