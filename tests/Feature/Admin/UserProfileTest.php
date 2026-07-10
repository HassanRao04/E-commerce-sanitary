<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->firstOrFail();
        Storage::fake('public');
    }

    public function test_admin_can_view_user_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Profile',
            'last_name' => 'User',
            'email' => 'profile.user@example.com',
            'phone' => '+92-300-9998877',
            'status' => UserStatus::Active,
            'last_login_at' => now()->subDay(),
            'last_login_ip' => '127.0.0.1',
        ]);
        $user->syncRoles(['manager']);

        ActivityLog::query()->create([
            'user_id' => $this->admin->id,
            'action' => 'user.updated',
            'description' => 'Updated profile for Profile User',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertSee('Profile User');
        $response->assertSee('profile.user@example.com');
        $response->assertSee('+92-300-9998877');
        $response->assertSee('Manager');
        $response->assertSee('Active');
        $response->assertSee('Recent Activity');
        $response->assertSee('Updated profile for Profile User');
        $response->assertSee('Edit User');
    }

    public function test_profile_shows_never_for_user_without_login(): void
    {
        $user = User::factory()->create([
            'email' => 'never.login@example.com',
            'last_login_at' => null,
        ]);
        $user->syncRoles(['sales-staff']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertSee('Never');
    }

    public function test_user_without_view_permission_cannot_access_profile(): void
    {
        $staff = User::factory()->create(['email' => 'inventory@example.com']);
        $staff->syncRoles(['inventory-staff']);

        $target = User::factory()->create(['email' => 'target@example.com']);
        $target->syncRoles(['manager']);

        $response = $this->actingAs($staff)->get(route('admin.users.show', $target));

        $response->assertForbidden();
    }
}
