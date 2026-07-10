<?php

namespace Tests\Feature\Admin;

use App\Enums\StaffRole;
use App\Enums\UserActivityAction;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 12 — consolidated verification of the User Management module.
 */
class UserManagementVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->superAdmin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_user_creation_editing_and_deletion_flow(): void
    {
        $create = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'first_name' => 'Verify',
            'last_name' => 'User',
            'email' => 'verify.user@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => StaffRole::Manager->value,
            'status' => UserStatus::Active->value,
        ]);

        $create->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'verify.user@example.com')->firstOrFail();

        $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'first_name' => 'Verified',
            'last_name' => 'User',
            'email' => 'verified.user@example.com',
            'role' => StaffRole::SalesStaff->value,
            'status' => UserStatus::Active->value,
        ])->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertSame('verified.user@example.com', $user->email);
        $this->assertTrue($user->hasRole(StaffRole::SalesStaff->value));

        $this->actingAs($this->superAdmin)->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_role_assignment_and_removal_are_audited(): void
    {
        $user = User::factory()->create(['email' => 'role.audit@example.com']);
        $user->syncRoles([StaffRole::InventoryStaff->value]);

        $this->actingAs($this->superAdmin)->patch(route('admin.users.role.update', $user), [
            'role' => StaffRole::Manager->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::RoleChanged->value,
            'model_id' => $user->id,
        ]);

        $this->actingAs($this->superAdmin)->delete(route('admin.users.role.destroy', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::RoleRemoved->value,
            'model_id' => $user->id,
        ]);
    }

    public function test_login_restrictions_for_suspended_and_inactive_staff(): void
    {
        $suspended = User::factory()->create([
            'email' => 'verify.suspended@example.com',
            'status' => UserStatus::Suspended,
        ]);
        $suspended->syncRoles([StaffRole::Admin->value]);

        $this->from(route('login'))->post('/login', [
            'email' => $suspended->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();

        $inactive = User::factory()->create([
            'email' => 'verify.inactive@example.com',
            'status' => UserStatus::Inactive,
        ]);
        $inactive->syncRoles([StaffRole::Admin->value]);

        $this->post('/login', [
            'email' => $inactive->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->actingAs($inactive)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_listing_search_filters_and_bulk_actions(): void
    {
        $match = User::factory()->create([
            'first_name' => 'Filter',
            'last_name' => 'Match',
            'email' => 'filter.match@example.com',
            'status' => UserStatus::Inactive,
        ]);
        $match->syncRoles([StaffRole::Manager->value]);

        User::factory()->create([
            'first_name' => 'Other',
            'last_name' => 'User',
            'email' => 'other.user@example.com',
            'status' => UserStatus::Active,
        ])->syncRoles([StaffRole::SalesStaff->value]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.users.index', [
                'name' => 'Filter',
                'role' => StaffRole::Manager->value,
                'status' => UserStatus::Inactive->value,
            ]))
            ->assertOk()
            ->assertSee('Filter Match')
            ->assertDontSee('Other User');

        $this->actingAs($this->superAdmin)->post(route('admin.users.bulk'), [
            'action' => 'activate',
            'user_ids' => [$match->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(UserStatus::Active, $match->fresh()->status);
    }

    public function test_admin_role_permissions_match_security_matrix(): void
    {
        $admin = User::factory()->create(['email' => 'matrix.admin@example.com']);
        $admin->syncRoles([StaffRole::Admin->value]);

        $target = User::factory()->create(['email' => 'matrix.target@example.com']);
        $target->syncRoles([StaffRole::SalesStaff->value]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'New',
            'last_name' => 'Staff',
            'email' => 'new.staff@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => StaffRole::SalesStaff->value,
            'status' => UserStatus::Active->value,
        ])->assertRedirect();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'action' => 'delete',
            'user_ids' => [$target->id],
        ])->assertForbidden();
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('admin.users.destroy', $this->superAdmin))
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $this->superAdmin->id]);
    }

    public function test_last_super_admin_cannot_be_demoted_via_update(): void
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

    public function test_bulk_action_rejects_soft_deleted_user_ids(): void
    {
        $deleted = User::factory()->create(['email' => 'already.deleted@example.com']);
        $deleted->syncRoles([StaffRole::Manager->value]);
        $deleted->delete();

        $this->actingAs($this->superAdmin)->post(route('admin.users.bulk'), [
            'action' => 'activate',
            'user_ids' => [$deleted->id],
        ])->assertSessionHasErrors('user_ids.0');
    }
}
