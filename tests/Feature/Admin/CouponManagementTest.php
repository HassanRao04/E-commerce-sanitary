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

    public function test_admin_can_create_influencer_coupon(): void
    {
        $influencer = User::factory()->create([
            'name' => 'Muhammad Hassan',
            'email' => 'hassan.influencer@example.com',
        ]);
        $influencer->assignRole('influencer');

        $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'HASSAN10',
                'type' => CouponType::Percent->value,
                'value' => 10,
                'is_active' => 1,
                'influencer_id' => $influencer->id,
                'commission_enabled' => 1,
                'commission_type' => CouponType::Percent->value,
                'commission_value' => 5,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'HASSAN10',
            'influencer_id' => $influencer->id,
            'commission_enabled' => 1,
            'commission_type' => CouponType::Percent->value,
            'commission_value' => 5,
        ]);
    }

    public function test_admin_can_create_fixed_commission_coupon(): void
    {
        $influencer = User::factory()->create([
            'name' => 'Fixed Influencer',
            'email' => 'fixed.influencer@example.com',
        ]);
        $influencer->assignRole('influencer');

        $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'FIXEDCOMM',
                'type' => CouponType::Fixed->value,
                'value' => 500,
                'is_active' => 1,
                'influencer_id' => $influencer->id,
                'commission_enabled' => 1,
                'commission_type' => CouponType::Fixed->value,
                'commission_value' => 100,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'FIXEDCOMM',
            'commission_enabled' => 1,
            'commission_type' => CouponType::Fixed->value,
            'commission_value' => 100,
        ]);
    }

    public function test_existing_coupon_works_without_influencer(): void
    {
        $this->assertDatabaseHas('coupons', [
            'code' => 'WELCOME10',
            'influencer_id' => null,
            'commission_enabled' => 0,
        ]);
    }

    public function test_coupon_form_lists_influencer_role_users(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Dropdown',
            'last_name' => 'Influencer',
            'name' => 'Dropdown Influencer',
            'email' => 'dropdown.influencer@example.com',
        ]);
        $influencer->assignRole('influencer');

        $staff = User::factory()->create([
            'first_name' => 'Not',
            'last_name' => 'Influencer',
            'name' => 'Not Influencer',
            'email' => 'not.influencer.staff@example.com',
        ]);
        $staff->assignRole('sales-staff');

        $this->actingAs($this->admin)
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertSee('Assign influencer')
            ->assertSee('dropdown.influencer@example.com')
            ->assertSee('Dropdown Influencer')
            ->assertDontSee('not.influencer.staff@example.com');
    }

    public function test_coupon_form_is_available(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertSee('Create coupon');
    }
}
