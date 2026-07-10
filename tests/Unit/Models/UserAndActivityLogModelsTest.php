<?php

namespace Tests\Unit\Models;

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAndActivityLogModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_exposes_roles_permissions_and_activity_logs_relationships(): void
    {
        $this->seed();

        $user = User::where('email', config('shop.admin_email'))->firstOrFail();
        $user->activityLogs()->create([
            'action' => 'user.updated',
            'description' => 'Profile updated',
            'ip_address' => '127.0.0.1',
            'browser' => 'PHPUnit',
        ]);

        $this->assertTrue(method_exists($user, 'roles'));
        $this->assertTrue(method_exists($user, 'permissions'));
        $this->assertCount(1, $user->fresh()->activityLogs);
        $this->assertTrue($user->roles->contains('name', 'super-admin'));
    }

    public function test_user_name_parts_sync_and_status_enum_cast(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => UserStatus::Active,
        ]);

        $user->refresh();

        $this->assertSame('Jane Doe', $user->full_name);
        $this->assertSame('Jane Doe', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertInstanceOf(UserStatus::class, $user->status);
    }

    public function test_suspended_status_sets_suspended_at_timestamp(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $user->update(['status' => UserStatus::Suspended]);

        $this->assertTrue($user->is_suspended);
        $this->assertNotNull($user->suspended_at);
    }

    public function test_activity_log_belongs_to_user_and_scopes(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        ActivityLog::query()->create([
            'user_id' => $actor->id,
            'action' => 'User.Created',
            'description' => 'Created staff user',
            'ip_address' => '10.0.0.1',
            'browser' => 'Chrome',
        ]);

        ActivityLog::query()->create([
            'user_id' => $target->id,
            'action' => 'user.login',
            'description' => 'Logged in',
            'ip_address' => '10.0.0.2',
            'browser' => 'Firefox',
        ]);

        $log = ActivityLog::query()->forUser($actor)->forAction('user.created')->first();

        $this->assertNotNull($log);
        $this->assertSame('user.created', $log->action);
        $this->assertSame('User Created', $log->action_label);
        $this->assertTrue($log->user->is($actor));
    }
}
