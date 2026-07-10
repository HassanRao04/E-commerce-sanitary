<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_create_staff_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'first_name' => 'Sara',
            'last_name' => 'Khan',
            'email' => 'sara.khan@example.com',
            'phone' => '+92-300-1234567',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'sales-staff',
            'status' => UserStatus::Active->value,
            'profile_photo' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'sara.khan@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Sara Khan', $user->full_name);
        $this->assertTrue($user->hasRole('sales-staff'));
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    public function test_create_user_requires_unique_email_and_strong_password(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'taken@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'role' => 'admin',
            'status' => UserStatus::Active->value,
        ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $manager = User::factory()->create([
            'email' => 'manager@example.com',
        ]);
        $manager->syncRoles(['admin']);

        $response = $this->actingAs($manager)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'first_name' => 'Bad',
            'last_name' => 'Actor',
            'email' => 'bad.actor@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'super-admin',
            'status' => UserStatus::Active->value,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'bad.actor@example.com']);
    }

    public function test_user_without_create_permission_cannot_store_user(): void
    {
        $staff = User::factory()->create(['email' => 'inventory@example.com']);
        $staff->syncRoles(['inventory-staff']);

        $response = $this->actingAs($staff)->post(route('admin.users.store'), [
            'first_name' => 'Blocked',
            'last_name' => 'User',
            'email' => 'blocked@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'sales-staff',
            'status' => UserStatus::Active->value,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_staff_user_profile_role_status_and_password(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'staff@example.com',
            'status' => UserStatus::Active,
        ]);
        $user->syncRoles(['sales-staff']);

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => 'Staff',
            'email' => 'updated.staff@example.com',
            'phone' => '+92-300-9999999',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
            'role' => 'manager',
            'status' => UserStatus::Suspended->value,
            'profile_photo' => UploadedFile::fake()->create('new-avatar.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Updated Staff', $user->full_name);
        $this->assertSame('updated.staff@example.com', $user->email);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertSame(UserStatus::Suspended, $user->status);
        $this->assertNotNull($user->suspended_at);
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);

        $this->assertDatabaseHas('activity_logs', ['action' => 'user.updated', 'model_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.role_changed', 'model_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.password_reset', 'model_id' => $user->id]);
    }

    public function test_non_super_admin_cannot_update_super_admin_user(): void
    {
        $admin = User::factory()->create(['email' => 'limited-admin@example.com']);
        $admin->syncRoles(['admin']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $this->admin), [
            'first_name' => 'Blocked',
            'last_name' => 'Update',
            'email' => $this->admin->email,
            'role' => 'super-admin',
            'status' => UserStatus::Active->value,
        ]);

        $response->assertForbidden();
    }

    public function test_update_without_password_keeps_existing_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'keep-pass@example.com',
            'password' => 'OriginalPass1!',
        ]);
        $user->syncRoles(['admin']);

        $originalHash = $user->password;

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'first_name' => 'Keep',
            'last_name' => 'Password',
            'email' => 'keep-pass@example.com',
            'role' => 'admin',
            'status' => UserStatus::Active->value,
        ]);

        $response->assertRedirect(route('admin.users.show', $user));

        $user->refresh();

        $this->assertSame($originalHash, $user->password);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'user.password_reset',
            'model_id' => $user->id,
        ]);
    }
}
