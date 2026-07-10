<?php

namespace Tests\Feature\Admin;

use App\Enums\UserActivityAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_login_creates_activity_log(): void
    {
        $user = User::factory()->create(['email' => 'login.log@example.com']);
        $user->syncRoles(['customer']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::Login->value,
            'model_type' => User::class,
            'model_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_logout_creates_activity_log(): void
    {
        $user = User::factory()->create(['email' => 'logout.log@example.com']);
        $user->syncRoles(['customer']);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::Logout->value,
            'model_type' => User::class,
            'model_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_delete_user_and_log_is_created(): void
    {
        $user = User::factory()->create(['email' => 'delete.me@example.com']);
        $user->syncRoles(['manager']);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::Deleted->value,
            'model_type' => User::class,
            'model_id' => $user->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_password_reset_via_forgot_password_creates_activity_log(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::factory()->create(['email' => 'reset.me@example.com']);

        $this->post('/forgot-password', ['email' => $user->email]);

        \Illuminate\Support\Facades\Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::PasswordReset->value,
            'model_type' => User::class,
            'model_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_activity_log_stores_ip_and_browser(): void
    {
        $user = User::factory()->create(['email' => 'meta.log@example.com']);
        $user->syncRoles(['customer']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'])
            ->actingAs($user)
            ->post('/logout');

        $this->assertDatabaseHas('activity_logs', [
            'action' => UserActivityAction::Logout->value,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.10',
        ]);

        $log = \App\Models\ActivityLog::query()->where('action', UserActivityAction::Logout->value)->latest('id')->first();
        $this->assertStringContainsString('Chrome', (string) $log->browser);
        $this->assertStringContainsString('Windows', (string) $log->browser);
    }
}
