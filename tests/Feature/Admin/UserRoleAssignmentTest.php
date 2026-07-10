<?php

namespace Tests\Feature\Admin;

use App\Enums\StaffRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->superAdmin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_admin_can_assign_role_on_create(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'first_name' => 'Role',
            'last_name' => 'Assign',
            'email' => 'role.assign@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => StaffRole::Manager->value,
            'status' => UserStatus::Active->value,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'role.assign@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole(StaffRole::Manager->value));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.role_assigned',
            'model_id' => $user->id,
        ]);
    }

    public function test_admin_can_change_role_via_dedicated_endpoint(): void
    {
        $user = User::factory()->create(['email' => 'change.role@example.com']);
        $user->syncRoles([StaffRole::SalesStaff->value]);

        $response = $this->actingAs($this->superAdmin)->patch(route('admin.users.role.update', $user), [
            'role' => StaffRole::ContentManager->value,
        ]);

        $response->assertRedirect(route('admin.users.edit', $user));

        $user->refresh();

        $this->assertTrue($user->hasRole(StaffRole::ContentManager->value));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.role_changed',
            'model_id' => $user->id,
        ]);
    }

    public function test_admin_can_remove_staff_role(): void
    {
        $user = User::factory()->create(['email' => 'remove.role@example.com']);
        $user->syncRoles([StaffRole::InventoryStaff->value]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.users.role.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertFalse($user->roles()->exists());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.role_removed',
            'model_id' => $user->id,
        ]);
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->create(['email' => 'limited@example.com']);
        $admin->syncRoles([StaffRole::Admin->value]);

        $user = User::factory()->create(['email' => 'target@example.com']);
        $user->syncRoles([StaffRole::Manager->value]);

        $response = $this->actingAs($admin)->patch(route('admin.users.role.update', $user), [
            'role' => StaffRole::SuperAdmin->value,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertTrue($user->fresh()->hasRole(StaffRole::Manager->value));
    }

    public function test_user_cannot_remove_their_own_role(): void
    {
        $admin = User::factory()->create(['email' => 'self.remove@example.com']);
        $admin->syncRoles([StaffRole::Admin->value]);

        $response = $this->actingAs($admin)->delete(route('admin.users.role.destroy', $admin));

        $response->assertForbidden();
        $this->assertTrue($admin->fresh()->hasRole(StaffRole::Admin->value));
    }

    public function test_cannot_remove_last_super_admin_role(): void
    {
        $this->assertSame(1, User::role(StaffRole::SuperAdmin->value)->count());

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.users.role.destroy', $this->superAdmin));

        $response->assertForbidden();
        $this->assertTrue($this->superAdmin->fresh()->hasRole(StaffRole::SuperAdmin->value));
    }

    public function test_cannot_demote_last_super_admin_via_update_form(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $this->superAdmin), [
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => $this->superAdmin->email,
            'role' => StaffRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertTrue($this->superAdmin->fresh()->hasRole(StaffRole::SuperAdmin->value));
    }

    public function test_inventory_staff_cannot_assign_roles(): void
    {
        $staff = User::factory()->create(['email' => 'inventory@example.com']);
        $staff->syncRoles([StaffRole::InventoryStaff->value]);

        $target = User::factory()->create(['email' => 'target2@example.com']);
        $target->syncRoles([StaffRole::SalesStaff->value]);

        $response = $this->actingAs($staff)->patch(route('admin.users.role.update', $target), [
            'role' => StaffRole::Manager->value,
        ]);

        $response->assertForbidden();
    }
}
