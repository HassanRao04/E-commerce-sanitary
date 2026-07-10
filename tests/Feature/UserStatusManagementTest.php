<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'status' => UserStatus::Suspended,
        ]);
        $user->assignRole('customer');

        $response = $this->from(route('login'))->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_user_can_login_but_cannot_access_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive-staff@example.com',
            'status' => UserStatus::Inactive,
        ]);
        $user->syncRoles(['admin']);

        $loginResponse = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $loginResponse->assertRedirect(route('shop.account.dashboard', absolute: false));

        $adminResponse = $this->actingAs($user)->get(route('admin.dashboard'));

        $adminResponse->assertForbidden();
    }

    public function test_active_staff_user_can_access_admin(): void
    {
        $admin = User::where('email', config('shop.admin_email'))->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_suspended_user_is_logged_out_when_accessing_protected_route(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('shop.account.dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_inactive_customer_can_access_storefront_account(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Inactive,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('shop.account.dashboard'));

        $response->assertOk();
    }

    public function test_successful_login_records_last_login_metadata(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'status' => UserStatus::Active,
        ]);
        $user->assignRole('customer');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('shop.account.dashboard', absolute: false));

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }
}
