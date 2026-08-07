<?php

namespace Tests\Feature\Admin;

use App\Models\CourierProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourierProviderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_courier_provider_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.courier-providers.index'))
            ->assertOk()
            ->assertSee('Courier Providers')
            ->assertSee('TCS')
            ->assertSee('Leopards Courier')
            ->assertSee('M&P')
            ->assertSee('Trax')
            ->assertSee('Call Courier');
    }

    public function test_admin_can_create_courier_provider(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.courier-providers.store'), [
                'name' => 'BlueEx',
                'slug' => 'blueex',
                'api_base_url' => 'https://api.blueex.com',
                'api_key' => 'test-key',
                'api_secret' => 'test-secret',
                'account_number' => 'ACC-001',
                'pickup_address' => 'Plot 12, Industrial Area',
                'pickup_city' => 'Karachi',
                'default_package_weight' => 1.5,
                'tracking_url_template' => 'https://blueex.com/track/{tracking_number}',
                'is_active' => '1',
                'is_sandbox' => '1',
                'sort_order' => 99,
            ])
            ->assertRedirect(route('admin.courier-providers.index'));

        $provider = CourierProvider::where('slug', 'blueex')->first();

        $this->assertNotNull($provider);
        $this->assertSame('BlueEx', $provider->name);
        $this->assertSame('https://api.blueex.com', $provider->api_base_url);
        $this->assertSame('ACC-001', $provider->account_number);
        $this->assertSame('Karachi', $provider->pickup_city);
        $this->assertSame('1.50', $provider->default_package_weight);
        $this->assertTrue($provider->is_active);
        $this->assertTrue($provider->is_sandbox);
        $this->assertSame('test-key', $provider->api_key);
        $this->assertSame('test-secret', $provider->api_secret);
    }

    public function test_admin_can_update_courier_provider_and_preserve_secret(): void
    {
        $provider = CourierProvider::where('slug', 'tcs')->firstOrFail();
        $provider->update([
            'credentials' => ['api_key' => 'saved-key', 'api_secret' => 'saved-secret'],
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.courier-providers.update', $provider), [
                'name' => 'TCS Express',
                'api_base_url' => 'https://api.tcs.example',
                'pickup_city' => 'Lahore',
                'is_active' => '1',
                'is_sandbox' => '0',
            ])
            ->assertRedirect(route('admin.courier-providers.index'));

        $provider->refresh();

        $this->assertSame('TCS Express', $provider->name);
        $this->assertSame('https://api.tcs.example', $provider->api_base_url);
        $this->assertSame('Lahore', $provider->pickup_city);
        $this->assertFalse($provider->is_sandbox);
        $this->assertSame('saved-key', $provider->api_key);
        $this->assertSame('saved-secret', $provider->api_secret);
    }

    public function test_admin_can_upload_courier_logo(): void
    {
        $provider = CourierProvider::where('slug', 'trax')->firstOrFail();
        $logo = UploadedFile::fake()->create('trax-logo.png', 100, 'image/png');

        $this->actingAs($this->admin)
            ->put(route('admin.courier-providers.update', $provider), [
                'name' => $provider->name,
                'logo' => $logo,
            ])
            ->assertRedirect(route('admin.courier-providers.index'));

        $provider->refresh();

        $this->assertNotNull($provider->logo);
        Storage::disk('public')->assertExists($provider->logo);
    }

    public function test_admin_can_delete_unused_courier_provider(): void
    {
        $provider = CourierProvider::create([
            'name' => 'Temp Courier',
            'slug' => 'temp_courier',
            'is_active' => false,
            'is_sandbox' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.courier-providers.destroy', $provider))
            ->assertRedirect(route('admin.courier-providers.index'));

        $this->assertDatabaseMissing('courier_providers', ['id' => $provider->id]);
    }

    public function test_manual_courier_provider_cannot_be_deleted(): void
    {
        $provider = CourierProvider::where('slug', 'manual')->firstOrFail();

        $this->actingAs($this->admin)
            ->from(route('admin.courier-providers.index'))
            ->delete(route('admin.courier-providers.destroy', $provider))
            ->assertRedirect(route('admin.courier-providers.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('courier_providers', ['id' => $provider->id]);
    }
}
