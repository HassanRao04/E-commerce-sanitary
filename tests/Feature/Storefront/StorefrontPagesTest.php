<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_storefront_static_pages_load(): void
    {
        $this->get(route('shop.about'))->assertOk()->assertSee('About');
        $this->get(route('shop.contact'))->assertOk()->assertSee('Contact us');
        $this->get(route('shop.orders.track'))->assertOk()->assertSee('Track your order');
        $this->get(route('shop.wishlist.index'))->assertOk()->assertSee('wishlist');
    }

    public function test_shop_route_redirects_from_legacy_products_path(): void
    {
        $this->get('/products?q=test')
            ->assertRedirect(route('shop.products.index', ['q' => 'test']));
    }

    public function test_guest_can_submit_contact_form(): void
    {
        $this->post(route('shop.contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '03001234567',
            'subject' => 'Product enquiry',
            'message' => 'I need help choosing a basin.',
        ])->assertRedirect(route('shop.contact'));

        $this->assertDatabaseHas('inquiries', [
            'email' => 'jane@example.com',
            'subject' => 'Product enquiry',
            'type' => 'contact',
        ]);
    }

    public function test_guest_can_add_product_to_wishlist(): void
    {
        $product = Product::query()->active()->first();
        $this->assertNotNull($product);

        $this->post(route('shop.wishlist.store'), [
            'product_id' => $product->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'product_id' => $product->id,
            'session_id' => session()->getId(),
        ]);
    }

    public function test_customer_can_access_account_dashboard(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('shop.account.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_guest_can_track_order_by_number_and_email(): void
    {
        $order = Order::query()->first();
        $this->assertNotNull($order);

        $this->post(route('shop.orders.track.show'), [
            'order_number' => $order->order_number,
            'email' => $order->customer_email,
        ])->assertOk()->assertSee($order->order_number);
    }

    public function test_products_index_supports_sort_and_filters(): void
    {
        $brand = Brand::query()->where('is_active', true)->first();
        $this->assertNotNull($brand);

        $this->get(route('shop.products.index', [
            'sort' => 'price_asc',
            'brand' => $brand->id,
            'q' => 'basin',
        ]))->assertOk()
            ->assertSee('products found');

        $this->get(route('shop.products.index', [
            'brands' => [$brand->id],
            'sort' => 'price_asc',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonStructure(['html', 'total']);
    }
}
