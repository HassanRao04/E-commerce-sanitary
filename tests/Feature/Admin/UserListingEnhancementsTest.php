<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListingEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_users_can_be_filtered_by_name_and_email(): void
    {
        User::factory()->create([
            'first_name' => 'Alpha',
            'last_name' => 'One',
            'email' => 'alpha.one@example.com',
        ])->syncRoles(['manager']);

        User::factory()->create([
            'first_name' => 'Beta',
            'last_name' => 'Two',
            'email' => 'beta.two@example.com',
        ])->syncRoles(['sales-staff']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['name' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha One')
            ->assertDontSee('Beta Two');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['email' => 'beta.two']))
            ->assertOk()
            ->assertSee('Beta Two')
            ->assertDontSee('Alpha One');
    }

    public function test_users_can_be_sorted_by_created_date_and_last_login(): void
    {
        $older = User::factory()->create([
            'email' => 'older@example.com',
            'created_at' => now()->subDays(5),
            'last_login_at' => now()->subDay(),
        ]);
        $older->syncRoles(['manager']);

        $newer = User::factory()->create([
            'email' => 'newer@example.com',
            'created_at' => now()->subDay(),
            'last_login_at' => now(),
        ]);
        $newer->syncRoles(['sales-staff']);

        $createdAsc = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'sort' => 'created_at',
            'direction' => 'asc',
        ]));

        $createdAsc->assertOk();
        $this->assertTrue(strpos($createdAsc->getContent(), 'older@example.com') < strpos($createdAsc->getContent(), 'newer@example.com'));

        $loginDesc = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'sort' => 'last_login_at',
            'direction' => 'desc',
        ]));

        $loginDesc->assertOk();
        $this->assertTrue(strpos($loginDesc->getContent(), 'newer@example.com') < strpos($loginDesc->getContent(), 'older@example.com'));
    }

    public function test_admin_can_bulk_activate_and_deactivate_users(): void
    {
        $user = User::factory()->create([
            'email' => 'bulk.status@example.com',
            'status' => UserStatus::Inactive,
        ]);
        $user->syncRoles(['manager']);

        $this->actingAs($this->admin)->post(route('admin.users.bulk'), [
            'action' => 'activate',
            'user_ids' => [$user->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(UserStatus::Active, $user->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.users.bulk'), [
            'action' => 'deactivate',
            'user_ids' => [$user->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(UserStatus::Inactive, $user->fresh()->status);
    }

    public function test_admin_can_bulk_delete_users(): void
    {
        $user = User::factory()->create(['email' => 'bulk.delete@example.com']);
        $user->syncRoles(['inventory-staff']);

        $this->actingAs($this->admin)->post(route('admin.users.bulk'), [
            'action' => 'delete',
            'user_ids' => [$user->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_bulk_delete_skips_super_admin_and_self(): void
    {
        $target = User::factory()->create(['email' => 'safe.delete@example.com']);
        $target->syncRoles(['manager']);

        $response = $this->actingAs($this->admin)->post(route('admin.users.bulk'), [
            'action' => 'delete',
            'user_ids' => [$target->id, $this->admin->id],
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }

    public function test_user_without_update_permission_cannot_bulk_activate(): void
    {
        $staff = User::factory()->create(['email' => 'viewer@example.com']);
        $staff->syncRoles(['inventory-staff']);

        $target = User::factory()->create(['email' => 'target@example.com', 'status' => UserStatus::Inactive]);
        $target->syncRoles(['manager']);

        $this->actingAs($staff)->post(route('admin.users.bulk'), [
            'action' => 'activate',
            'user_ids' => [$target->id],
        ])->assertForbidden();
    }
}
