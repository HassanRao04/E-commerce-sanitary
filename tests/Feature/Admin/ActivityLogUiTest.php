<?php

namespace Tests\Feature\Admin;

use App\Enums\UserActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_admin_can_view_activity_log_index(): void
    {
        ActivityLog::query()->create([
            'user_id' => $this->admin->id,
            'action' => UserActivityAction::Login->value,
            'description' => 'Signed in',
            'model_type' => User::class,
            'model_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
            'browser' => 'Chrome on Windows',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.activity.index'));

        $response->assertOk();
        $response->assertSee('Activity Log');
        $response->assertSee('Signed in');
        $response->assertSee('127.0.0.1');
        $response->assertSee('Chrome on Windows');
    }

    public function test_activity_log_can_be_filtered_by_subject_user(): void
    {
        $target = User::factory()->create(['email' => 'subject@example.com']);
        $target->syncRoles(['manager']);

        ActivityLog::query()->create([
            'user_id' => $this->admin->id,
            'action' => UserActivityAction::Updated->value,
            'description' => 'Updated profile for Subject User',
            'model_type' => User::class,
            'model_id' => $target->id,
        ]);

        ActivityLog::query()->create([
            'user_id' => $this->admin->id,
            'action' => UserActivityAction::Updated->value,
            'description' => 'Updated profile for Other User',
            'model_type' => User::class,
            'model_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.activity.index', ['subject' => $target->id]));

        $response->assertOk();
        $response->assertSee('Updated profile for Subject User');
        $response->assertDontSee('Updated profile for Other User');
    }

    public function test_staff_without_permission_cannot_view_activity_log(): void
    {
        $staff = User::factory()->create(['email' => 'inventory@example.com']);
        $staff->syncRoles(['inventory-staff']);

        $this->actingAs($staff)->get(route('admin.activity.index'))->assertForbidden();
    }
}
