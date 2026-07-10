<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->superAdmin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_super_admin_has_full_user_access(): void
    {
        $target = User::factory()->create(['email' => 'full.access@example.com']);
        $target->syncRoles(['manager']);

        $this->actingAs($this->superAdmin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.users.show', $target))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.users.edit', $target))->assertOk();
        $this->actingAs($this->superAdmin)->delete(route('admin.users.destroy', $target))->assertRedirect();
    }

    public function test_admin_can_view_create_and_edit_but_not_delete_users(): void
    {
        $admin = User::factory()->create(['email' => 'role.admin@example.com']);
        $admin->syncRoles(['admin']);

        $target = User::factory()->create(['email' => 'admin.target@example.com']);
        $target->syncRoles(['sales-staff']);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $target))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $target))->assertOk();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertForbidden();

        $this->assertFalse($admin->hasPermissionTo('users.delete'));
        $this->assertTrue($admin->hasPermissionTo('users.view'));
        $this->assertTrue($admin->hasPermissionTo('users.create'));
        $this->assertTrue($admin->hasPermissionTo('users.update'));
    }

    public function test_manager_can_view_users_only(): void
    {
        $manager = User::factory()->create(['email' => 'role.manager@example.com']);
        $manager->syncRoles(['manager']);

        $target = User::factory()->create(['email' => 'manager.target@example.com']);
        $target->syncRoles(['sales-staff']);

        $this->actingAs($manager)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.users.show', $target))->assertOk();
        $this->actingAs($manager)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.users.edit', $target))->assertForbidden();
        $this->actingAs($manager)->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.users.bulk'), [
            'action' => 'activate',
            'user_ids' => [$target->id],
        ])->assertForbidden();
    }

    public function test_inventory_sales_and_content_roles_have_no_user_access(): void
    {
        $target = User::factory()->create(['email' => 'blocked.target@example.com']);
        $target->syncRoles(['manager']);

        foreach (['inventory-staff', 'sales-staff', 'content-manager'] as $role) {
            $staff = User::factory()->create(['email' => "{$role}@example.com"]);
            $staff->syncRoles([$role]);

            $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($staff)->get(route('admin.users.show', $target))->assertForbidden();
        }
    }

    public function test_all_user_routes_are_protected_by_permission_middleware(): void
    {
        $expectedPermissions = [
            'admin.users.index' => 'users.view',
            'admin.users.show' => 'users.view',
            'admin.users.create' => 'users.create',
            'admin.users.store' => 'users.create',
            'admin.users.edit' => 'users.update',
            'admin.users.update' => 'users.update',
            'admin.users.role.update' => 'users.update',
            'admin.users.role.destroy' => 'users.update',
            'admin.users.bulk' => 'users.update',
            'admin.users.destroy' => 'users.delete',
        ];

        foreach ($expectedPermissions as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Missing route: {$routeName}");

            $middleware = collect($route->gatherMiddleware());

            $this->assertTrue(
                $middleware->contains("permission:{$permission}"),
                "Route {$routeName} must use permission:{$permission} middleware.",
            );
        }
    }
}
