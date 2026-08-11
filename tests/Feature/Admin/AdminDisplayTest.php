<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminDateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->superAdmin = User::where('email', config('shop.admin_email'))->firstOrFail();
    }

    public function test_deleted_records_shows_same_actor_for_cascaded_categories(): void
    {
        $parent = Category::query()->create([
            'name' => 'Cascade Parent',
            'slug' => 'cascade-parent-display',
            'is_active' => true,
        ]);

        $child = $parent->children()->create([
            'name' => 'Cascade Child',
            'slug' => 'cascade-child-display',
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.categories.destroy', $parent));

        $response = $this->actingAs($this->superAdmin)->get(route('admin.deleted-records.index', [
            'type' => 'category',
        ]));

        $response->assertOk();
        $response->assertSee($parent->name);
        $response->assertSee($child->name);
        $response->assertSee($this->superAdmin->full_name);
        $response->assertDontSee('Unknown');
    }

    public function test_deleted_records_displays_timestamps_in_display_timezone(): void
    {
        $product = Product::firstOrFail();
        $utc = Carbon::create(2026, 8, 10, 8, 47, 0, 'UTC');

        $product->deleted_at = $utc;
        $product->save();

        ActivityLog::query()->create([
            'user_id' => $this->superAdmin->id,
            'action' => 'product.deleted',
            'description' => 'Deleted product for timezone test',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'old_values' => $product->toArray(),
            'created_at' => $utc,
        ]);

        $expected = AdminDateTime::format($utc);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.deleted-records.index', [
            'type' => 'product',
        ]));

        $response->assertOk();
        $response->assertSee($expected, false);
    }

    public function test_activity_log_displays_timestamps_in_display_timezone(): void
    {
        $utc = Carbon::create(2026, 8, 10, 8, 47, 0, 'UTC');

        $log = ActivityLog::query()->create([
            'user_id' => $this->superAdmin->id,
            'action' => 'product.created',
            'description' => 'Timezone display test',
            'model_type' => Product::class,
            'model_id' => Product::firstOrFail()->id,
        ]);

        $log->created_at = $utc;
        $log->saveQuietly();

        $expected = AdminDateTime::format($utc);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.activity.index', [
            'q' => 'Timezone display test',
        ]));

        $response->assertOk();
        $response->assertSee($expected, false);
    }

    public function test_admin_layout_shows_human_readable_role_label(): void
    {
        $inventoryStaff = User::factory()->create([
            'email' => 'inventory.display@example.com',
            'name' => 'Inventory Display User',
            'status' => 'active',
        ]);
        $inventoryStaff->syncRoles(['inventory-staff']);

        $this->actingAs($inventoryStaff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Inventory Staff')
            ->assertDontSee('inventory-staff');

        $manager = User::factory()->create([
            'email' => 'manager.display@example.com',
            'name' => 'Manager Display User',
            'status' => 'active',
        ]);
        $manager->syncRoles(['manager']);

        $this->actingAs($manager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Manager');
    }

    public function test_manager_can_create_but_not_delete_product(): void
    {
        $manager = User::factory()->create([
            'email' => 'manager.product.auth@example.com',
            'status' => 'active',
        ]);
        $manager->syncRoles(['manager']);

        $product = Product::firstOrFail();

        $this->actingAs($manager)->get(route('admin.products.create'))->assertOk();
        $this->actingAs($manager)->delete(route('admin.products.destroy', $product))->assertForbidden();
    }

    public function test_inventory_staff_cannot_create_product(): void
    {
        $staff = User::factory()->create([
            'email' => 'inventory.product.auth@example.com',
            'status' => 'active',
        ]);
        $staff->syncRoles(['inventory-staff']);

        $this->actingAs($staff)->get(route('admin.products.create'))->assertForbidden();
    }
}
