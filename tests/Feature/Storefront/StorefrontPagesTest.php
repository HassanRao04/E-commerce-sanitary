<?php

namespace Tests\Feature\Storefront;

use App\Mail\ContactInquiryMail;
use App\Models\Brand;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        Mail::fake();

        \App\Models\SiteSetting::current()->update([
            'whatsapp' => '+92-331-4324807',
            'support_email' => 'inayatsanitaryhouse@gmail.com',
            'contact_form_enabled' => true,
            'email_notifications_enabled' => true,
            'whatsapp_notifications_enabled' => true,
        ]);

        $response = $this->post(route('shop.contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '03001234567',
            'subject' => 'Product enquiry',
            'message' => 'I need help choosing a basin.',
        ]);

        $response->assertRedirect(route('shop.contact.success'));
        $response->assertSessionHas('contact_success');

        $success = session('contact_success');
        $this->assertMatchesRegularExpression('/^INQ-\d{6}$/', $success['reference_id']);
        $whatsappUrl = $success['whatsapp_url'];
        $this->assertIsString($whatsappUrl);
        $this->assertStringStartsWith('https://wa.me/923314324807?', $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Inquiry ID:'), $whatsappUrl);

        $this->assertDatabaseHas('inquiries', [
            'email' => 'jane@example.com',
            'subject' => 'Product enquiry',
            'type' => 'contact',
            'source' => Inquiry::SOURCE_CONTACT_FORM,
        ]);

        Mail::assertSent(ContactInquiryMail::class, function (ContactInquiryMail $mail): bool {
            return $mail->hasTo('inayatsanitaryhouse@gmail.com')
                && $mail->inquiry->email === 'jane@example.com'
                && $mail->envelope()->subject === 'New Customer Inquiry';
        });
    }

    public function test_contact_success_page_displays_reference_id(): void
    {
        $this->withSession([
            'contact_success' => [
                'reference_id' => 'INQ-000042',
                'whatsapp_url' => null,
            ],
        ])->get(route('shop.contact.success'))
            ->assertOk()
            ->assertSee('INQ-000042')
            ->assertSee('Continue Shopping');
    }

    public function test_contact_form_respects_erp_notification_settings(): void
    {
        Mail::fake();

        \App\Models\SiteSetting::current()->update([
            'support_email' => 'support@example.com',
            'whatsapp' => '+92-331-4324807',
            'contact_form_enabled' => true,
            'email_notifications_enabled' => false,
            'whatsapp_notifications_enabled' => false,
        ]);

        $this->post(route('shop.contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '03001234567',
            'subject' => 'Product enquiry',
            'message' => 'I need help choosing a basin.',
        ])->assertRedirect(route('shop.contact.success'))
            ->assertSessionHas('contact_success', fn (array $session): bool => ($session['whatsapp_url'] ?? null) === null);

        Mail::assertNothingSent();
    }

    public function test_disabled_contact_form_rejects_submissions(): void
    {
        Mail::fake();

        \App\Models\SiteSetting::current()->update([
            'contact_form_enabled' => false,
        ]);

        $this->post(route('shop.contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Product enquiry',
            'message' => 'I need help choosing a basin.',
        ])->assertRedirect(route('shop.contact'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('inquiries', [
            'email' => 'jane@example.com',
        ]);

        Mail::assertNothingSent();
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

    public function test_shop_page_loads(): void
    {
        $this->get(route('shop.products.index'))
            ->assertOk()
            ->assertSee('Shop');
    }

    public function test_category_page_loads_products(): void
    {
        $category = \App\Models\Category::query()->active()->first();
        $this->assertNotNull($category);

        $this->get(route('shop.categories.show', $category))
            ->assertOk()
            ->assertSee($category->name);
    }

    public function test_legacy_category_url_redirects_to_shop_category_path(): void
    {
        $category = \App\Models\Category::query()->active()->first();
        $this->assertNotNull($category);

        $this->get('/categories/'.$category->slug)
            ->assertRedirect(route('shop.categories.show', $category));
    }

    public function test_unknown_category_shows_custom_404_page(): void
    {
        $this->get(route('shop.categories.show', ['category' => 'does-not-exist']))
            ->assertNotFound()
            ->assertSee('Page not found');
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
