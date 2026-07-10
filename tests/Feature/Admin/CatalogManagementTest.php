<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_admin_can_view_products_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Products');
    }

    public function test_admin_can_create_product(): void
    {
        $brand = Brand::first();
        $category = Category::first();

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Test Basin Mixer',
            'slug' => 'test-basin-mixer',
            'base_sku' => 'TEST-001',
            'brand_id' => $brand->id,
            'status' => 'active',
            'price' => 5000,
            'stock_quantity' => 10,
            'category_ids' => [$category->id],
        ]);

        $response->assertRedirect();
        $product = Product::where('slug', 'test-basin-mixer')->first();
        $this->assertNotNull($product);
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseHas('products', ['slug' => 'test-basin-mixer']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'TEST-001']);
    }

    public function test_admin_can_view_brands_and_categories(): void
    {
        $this->actingAs($this->admin)->get(route('admin.brands.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.categories.index'))->assertOk();
    }

    public function test_admin_can_create_brand(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.brands.store'), [
            'name' => 'New Brand',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', ['name' => 'New Brand']);
    }

    public function test_admin_can_upload_category_homepage_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Basins',
            'slug' => 'basins-card',
            'sort_order' => 1,
            'is_active' => true,
            'image' => UploadedFile::fake()->create('basins.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'basins-card')->firstOrFail();

        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee($category->image_url, false);
    }

    public function test_admin_can_view_inventory(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee('Inventory');
    }

    public function test_seeded_products_exist_after_seed(): void
    {
        $this->assertTrue(Product::query()->count() >= 6);
    }
}
