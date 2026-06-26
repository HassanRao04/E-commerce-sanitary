<?php

namespace Tests\Feature\Storefront;

use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_storefront_home_page_loads(): void
    {
        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('Featured products')
            ->assertSee('Best selling products')
            ->assertSee('New arrivals')
            ->assertSee('Trending products')
            ->assertSee('Why choose us')
            ->assertSee('Delivery benefits that matter')
            ->assertSee('Loved by thousands of customers')
            ->assertSee('Shop with confidence')
            ->assertSee('Flexible payment options')
            ->assertSee('Unlock exclusive offers')
            ->assertSee('Newsletter')
            ->assertSee('Shipping policy');
    }

    public function test_guest_can_subscribe_to_newsletter(): void
    {
        $response = $this->from(route('shop.home'))
            ->post(route('shop.newsletter.store'), [
                'email' => 'subscriber@example.com',
            ]);

        $response
            ->assertRedirect(route('shop.home'))
            ->assertSessionHas('newsletter_success');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'subscriber@example.com',
        ]);
    }

    public function test_newsletter_rejects_invalid_email(): void
    {
        $this->from(route('shop.home'))
            ->post(route('shop.newsletter.store'), [
                'email' => 'not-an-email',
            ])
            ->assertRedirect(route('shop.home'))
            ->assertSessionHasErrors('email');
    }

    public function test_products_index_and_show_pages_load(): void
    {
        $product = Product::query()->active()->first();
        $this->assertNotNull($product);

        $this->get(route('shop.products.index'))
            ->assertOk()
            ->assertSee($product->name);

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_guest_can_add_to_cart_but_must_login_to_checkout(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'quantity' => 1,
        ])->assertRedirect(route('shop.cart.index'));

        $this->get(route('shop.cart.index'))
            ->assertOk()
            ->assertSee($product->name);

        $this->get(route('shop.checkout.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_customer_can_checkout_with_cod(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();
        $variant = $product->defaultVariant;
        $initialStock = $variant->stock_quantity;

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'shipping_line1' => '123 Main Street',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);
        $response->assertRedirect(route('shop.checkout.success', $order));

        $this->get(route('shop.checkout.success', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('View my orders');

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{6}$/', $order->order_number);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->assertEquals($initialStock - 1, $variant->fresh()->stock_quantity);
    }

    public function test_guest_can_apply_coupon_to_cart(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'quantity' => 1,
        ]);

        $this->from(route('shop.cart.index'))
            ->post(route('shop.cart.coupon.apply'), [
                'code' => 'WELCOME10',
            ])->assertRedirect(route('shop.cart.index'));

        $this->get(route('shop.cart.index'))
            ->assertOk()
            ->assertSee('WELCOME10');
    }

    public function test_bank_transfer_checkout_redirects_to_payment_instructions(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03007654321',
                'payment_method' => PaymentMethod::BankTransfer->value,
                'shipping_line1' => '456 Bank Road',
                'shipping_city' => 'Lahore',
                'shipping_country' => 'Pakistan',
            ]);

        $order = Order::query()->find(session('shop.last_order_id'));
        $response->assertRedirect(route('shop.payment.show', $order));

        $this->actingAs($customer)
            ->get(route('shop.payment.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee(config('payments.bank_transfer.bank_name'));
    }

    public function test_empty_cart_cannot_checkout(): void
    {
        $customer = User::query()->role('customer')->first();

        $this->actingAs($customer)
            ->get(route('shop.checkout.index'))
            ->assertRedirect(route('shop.cart.index'));
    }

    public function test_customer_can_checkout_with_billing_and_shipping_addresses(): void
    {
        $customer = User::query()->role('customer')->first();
        $this->assertNotNull($customer);

        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'billing_same_as_shipping' => '0',
                'shipping_line1' => '10 Customer Street',
                'shipping_city' => 'Islamabad',
                'shipping_country' => 'Pakistan',
                'billing_line1' => '20 Billing Avenue',
                'billing_city' => 'Rawalpindi',
                'billing_country' => 'Pakistan',
            ]);

        $order = Order::query()->where('user_id', $customer->id)->whereHas('shippingAddress', function ($q): void {
            $q->where('line1', '10 Customer Street');
        })->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('shop.checkout.success', $order));

        $this->assertNotNull($order->shipping_address_id);
        $this->assertNotNull($order->billing_address_id);
        $this->assertNotEquals($order->shipping_address_id, $order->billing_address_id);
    }

    public function test_checkout_calculates_tax_shipping_and_coupon(): void
    {
        config([
            'shop.tax_rate' => 17,
            'shop.shipping_flat_rate' => 500,
            'shop.free_shipping_threshold' => 999999,
        ]);

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->post(route('shop.cart.coupon.apply'), ['code' => 'WELCOME10']);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03009998877',
                'payment_method' => PaymentMethod::Cod->value,
                'billing_same_as_shipping' => '1',
                'shipping_line1' => '1 Test Road',
                'shipping_city' => 'Karachi',
                'shipping_country' => 'Pakistan',
            ]);

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);
        $this->assertGreaterThan(0, $order->discount_total);
        $this->assertEquals(500.0, (float) $order->shipping_total);
        $this->assertGreaterThan(0, $order->tax_total);
        $this->assertEquals('WELCOME10', $order->coupon_code);
    }

    public function test_checkout_page_shows_order_summary_and_coupon_form(): void
    {
        $customer = User::query()->role('customer')->first();
        $product = Product::query()->active()->with('defaultVariant')->first();

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($customer)
            ->get(route('shop.checkout.index'))
            ->assertOk()
            ->assertSee('Order summary')
            ->assertSee('Billing address')
            ->assertSee('Coupon code')
            ->assertSee($product->name);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::query()->role('customer')->first();
        $other = User::factory()->create();
        $other->assignRole('customer');

        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('shop.account.orders.show', $order))
            ->assertForbidden();
    }
}
