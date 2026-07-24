<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_admin_can_view_influencers_page(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Influencer',
            'last_name' => 'One',
            'name' => 'Influencer One',
            'email' => 'influencer.one@example.com',
            'phone' => '+92-300-0000001',
            'status' => UserStatus::Active,
        ]);
        $influencer->assignRole('influencer');

        $response = $this->actingAs($this->admin)->get(route('admin.influencers.index'));

        $response->assertOk();
        $response->assertSee('Influencers');
        $response->assertSee('Influencer One');
        $response->assertSee('influencer.one@example.com');
        $response->assertSee('+92-300-0000001');
    }

    public function test_users_index_still_works(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_admin_can_create_influencer(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.influencers.store'), [
            'name' => 'Ayesha Influencer',
            'email' => 'ayesha.influencer@example.com',
            'phone' => '+92-300-1112233',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'status' => UserStatus::Active->value,
            'notes' => 'Campaign partner',
        ]);

        $response->assertRedirect(route('admin.influencers.index'));

        $user = User::where('email', 'ayesha.influencer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('influencer'));
        $this->assertSame('+92-300-1112233', $user->phone);
        $this->assertSame('Campaign partner', $user->notes);

        $this->actingAs($this->admin)
            ->get(route('admin.influencers.index'))
            ->assertOk()
            ->assertSee('Ayesha Influencer')
            ->assertSee('ayesha.influencer@example.com');
    }

    public function test_admin_can_edit_activate_deactivate_and_soft_delete_influencer(): void
    {
        $influencer = User::factory()->create([
            'first_name' => 'Edit',
            'last_name' => 'Me',
            'name' => 'Edit Me',
            'email' => 'edit.me@example.com',
            'phone' => '+92-300-4445566',
            'status' => UserStatus::Active,
        ]);
        $influencer->assignRole('influencer');

        $this->actingAs($this->admin)
            ->put(route('admin.influencers.update', $influencer), [
                'name' => 'Edited Influencer',
                'email' => 'edited.influencer@example.com',
                'phone' => '+92-300-7778899',
                'status' => UserStatus::Active->value,
                'notes' => 'Updated notes',
            ])
            ->assertRedirect(route('admin.influencers.index'));

        $influencer->refresh();
        $this->assertSame('Edited Influencer', $influencer->full_name);
        $this->assertSame('edited.influencer@example.com', $influencer->email);
        $this->assertTrue($influencer->hasRole('influencer'));

        $this->actingAs($this->admin)
            ->patch(route('admin.influencers.deactivate', $influencer))
            ->assertRedirect(route('admin.influencers.index'));

        $this->assertSame(UserStatus::Inactive, $influencer->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.influencers.activate', $influencer))
            ->assertRedirect(route('admin.influencers.index'));

        $this->assertSame(UserStatus::Active, $influencer->fresh()->status);

        $this->actingAs($this->admin)
            ->delete(route('admin.influencers.destroy', $influencer))
            ->assertRedirect(route('admin.influencers.index'));

        $this->assertSoftDeleted($influencer);
        $this->assertDatabaseMissing('model_has_roles', [
            'model_id' => $influencer->id,
            'model_type' => User::class,
        ]);
    }
}
