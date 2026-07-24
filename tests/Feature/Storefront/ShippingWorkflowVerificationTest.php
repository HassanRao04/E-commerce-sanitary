<?php

namespace Tests\Feature\Storefront;

use App\Enums\PaymentMethod;
use App\Enums\ShippingMethod;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CategoryShippingRate;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductShippingRate;
use App\Models\ShippingSetting;
use App\Models\TaxChargeSetting;
use App\Models\User;
use App\Services\Admin\InvoiceService;
use App\Services\CheckoutPricingService;
use App\Services\CheckoutRulesEngine;
use App\Services\ShippingCalculatorService;
use App\Services\ShippingSettingsService;
use App\Services\TaxChargeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — end-to-end shipping verification across methods, free rules,
 * coupons/tax, order persistence, and display surfaces.
 */
class ShippingWorkflowVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $this->admin = User::where('email', config('shop.admin_email'))->first();

        TaxChargeSetting::current()->update([
            'gst_enabled' => true,
            'gst_rate' => 10,
            'default_tax_type' => 'gst',
            'service_charge_enabled' => false,
            'handling_charge_enabled' => false,
        ]);
        app(TaxChargeSettingsService::class)->clearCache();
    }

    public function test_flat_rate_small_order_is_consistent_across_surfaces(): void
    {
        $this->configureFlat(300, 10000);

        $product = $this->firstProduct();
        $this->addToCart($product, 1, 2000);

        $expectedShipping = 300.0;
        $this->assertSurfaceConsistency($expectedShipping, expectFreeLabel: false);
        $order = $this->placeOrder();
        $this->assertPersistedShipping($order, $expectedShipping);
    }

    public function test_free_shipping_threshold_large_order(): void
    {
        $this->configureFlat(300, 10000);

        $product = $this->firstProduct();
        $unitPrice = (float) $product->defaultVariant->price;
        $quantity = max(1, (int) ceil(12000 / max(1, $unitPrice)));

        $this->actingAs($this->customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => $quantity,
            ])
            ->assertRedirect();

        $cart = $this->customerCart();
        $this->assertGreaterThanOrEqual(10000, app(CheckoutRulesEngine::class)->calculate($cart)['discounted_subtotal']);

        $this->assertSurfaceConsistency(0.0, expectFreeLabel: true);
        $order = $this->placeOrder();
        $this->assertPersistedShipping($order, 0.0);
    }

    public function test_product_based_shipping_with_multiple_products(): void
    {
        $products = Product::query()->active()->with(['defaultVariant', 'categories'])->limit(3)->get();
        $this->assertGreaterThanOrEqual(3, $products->count());

        $this->configureMethod(ShippingMethod::Product, [
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => false,
        ]);

        $amounts = [200.0, 600.0, 500.0];
        foreach ($products as $index => $product) {
            ProductShippingRate::updateOrCreate(
                ['product_id' => $product->id],
                ['amount' => $amounts[$index], 'free_shipping' => false, 'is_active' => true],
            );
            $this->addToCart($product, 1, 1000);
        }
        app(ShippingSettingsService::class)->clearCache();

        $expected = 1300.0;
        $engine = app(CheckoutRulesEngine::class)->calculate($this->customerCart());
        $this->assertEquals($expected, $engine['shipping']);
        $this->assertSame('product', $engine['shipping_method']);

        $this->assertSurfaceConsistency($expected, expectFreeLabel: false);
        $order = $this->placeOrder();
        $this->assertPersistedShipping($order, $expected);
    }

    public function test_category_based_shipping_with_mixed_categories(): void
    {
        $categories = Category::query()->active()->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $categories->count());

        $productA = Product::query()->active()->with('defaultVariant')
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categories[0]->id))
            ->first() ?? $this->attachProductToCategory($categories[0]);

        $productB = Product::query()->active()->with('defaultVariant')
            ->where('id', '!=', $productA->id)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categories[1]->id))
            ->first() ?? $this->attachProductToCategory($categories[1], excludeId: $productA->id);

        $this->configureMethod(ShippingMethod::Category, [
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => false,
        ]);

        CategoryShippingRate::updateOrCreate(
            ['category_id' => $categories[0]->id],
            ['amount' => 150, 'is_active' => true],
        );
        CategoryShippingRate::updateOrCreate(
            ['category_id' => $categories[1]->id],
            ['amount' => 250, 'is_active' => true],
        );
        app(ShippingSettingsService::class)->clearCache();

        $this->addToCart($productA->fresh('defaultVariant'), 1, 1000);
        $this->addToCart($productB->fresh('defaultVariant'), 1, 1000);

        $expected = 400.0;
        $engine = app(CheckoutRulesEngine::class)->calculate($this->customerCart());
        $this->assertEquals($expected, $engine['shipping']);
        $this->assertSame('category', $engine['shipping_method']);

        $this->assertSurfaceConsistency($expected, expectFreeLabel: false);
        $order = $this->placeOrder();
        $this->assertPersistedShipping($order, $expected);
    }

    public function test_coupon_and_tax_do_not_change_flat_shipping_amount(): void
    {
        $this->configureFlat(300, 10000);

        $product = $this->firstProduct();
        $this->addToCart($product, 1, 6000);

        $coupon = Coupon::query()->where('code', 'WELCOME10')->first();
        $this->assertNotNull($coupon);
        $this->customerCart()->update(['coupon_id' => $coupon->id]);

        $pricing = app(CheckoutPricingService::class)->calculate($this->customerCart());
        $engine = app(CheckoutRulesEngine::class)->calculate($this->customerCart());

        $this->assertEquals(300.0, $pricing['shipping']);
        $this->assertEquals(300.0, $engine['shipping']);
        $this->assertGreaterThan(0, $engine['discount']);
        $this->assertGreaterThan(0, $engine['tax']);
        $this->assertEquals(
            round($engine['discounted_subtotal'] + $engine['shipping'] + $engine['tax'], 2),
            $engine['grand_total'],
        );

        $this->assertSurfaceConsistency(300.0, expectFreeLabel: false);
        $order = $this->placeOrder();
        $this->assertPersistedShipping($order, 300.0);
        $this->assertGreaterThan(0, (float) $order->discount_total);
        $this->assertGreaterThan(0, (float) $order->tax_total);
    }

    public function test_calculator_methods_match_engine_for_all_charge_modes(): void
    {
        $product = $this->firstProduct();
        $cart = Cart::query()->create(['user_id' => $this->customer->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'quantity' => 1,
            'unit_price' => 2500,
        ]);
        $cart = $cart->fresh(['items.product.categories', 'coupon']);

        $this->configureFlat(300, 10000);
        $calc = app(ShippingCalculatorService::class)->calculate($cart, 2500.0);
        $engine = app(CheckoutRulesEngine::class)->calculate($cart);
        $this->assertEquals($calc['shipping'], $engine['shipping']);
        $this->assertEquals(300.0, $engine['shipping']);

        ProductShippingRate::updateOrCreate(
            ['product_id' => $product->id],
            ['amount' => 450, 'free_shipping' => false, 'is_active' => true],
        );
        $this->configureMethod(ShippingMethod::Product, ['free_shipping_enabled' => false]);
        $calc = app(ShippingCalculatorService::class)->calculate($cart->fresh(['items.product.categories']), 2500.0);
        $engine = app(CheckoutRulesEngine::class)->calculate($cart->fresh(['items.product.categories', 'coupon']));
        $this->assertEquals($calc['shipping'], $engine['shipping']);
        $this->assertEquals(450.0, $engine['shipping']);

        $category = $product->categories->first() ?? Category::query()->first();
        $product->categories()->syncWithoutDetaching([$category->id]);
        CategoryShippingRate::updateOrCreate(
            ['category_id' => $category->id],
            ['amount' => 175, 'is_active' => true],
        );
        $this->configureMethod(ShippingMethod::Category, ['free_shipping_enabled' => false]);
        $calc = app(ShippingCalculatorService::class)->calculate($cart->fresh(['items.product.categories']), 2500.0);
        $engine = app(CheckoutRulesEngine::class)->calculate($cart->fresh(['items.product.categories', 'coupon']));
        $this->assertEquals($calc['shipping'], $engine['shipping']);
        $this->assertEquals(175.0, $engine['shipping']);
    }

    private function configureFlat(float $amount, float $threshold): void
    {
        $this->configureMethod(ShippingMethod::Flat, [
            'flat_rate_amount' => $amount,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => $threshold,
        ]);
    }

    /** @param  array<string, mixed>  $extra */
    private function configureMethod(ShippingMethod $method, array $extra = []): void
    {
        $data = array_merge([
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 10000,
            'default_method' => $method,
        ], $extra);

        $service = app(ShippingSettingsService::class);
        $data = $service->normalizeMethodFlags($data);
        ShippingSetting::current()->update($data);
        $service->clearCache();
    }

    private function firstProduct(): Product
    {
        $product = Product::query()->active()->with('defaultVariant')->first();
        $this->assertNotNull($product);

        return $product;
    }

    private function addToCart(Product $product, int $qty, float $unitPrice): void
    {
        $this->actingAs($this->customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => $qty,
            ])
            ->assertRedirect();

        $item = CartItem::query()
            ->where('cart_id', $this->customerCart()->id)
            ->where('product_id', $product->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($item);
        $item->update(['unit_price' => $unitPrice]);
    }

    private function customerCart(): Cart
    {
        return Cart::query()
            ->where('user_id', $this->customer->id)
            ->with(['items.product.categories', 'coupon'])
            ->latest('id')
            ->firstOrFail();
    }

    private function assertSurfaceConsistency(float $expectedShipping, bool $expectFreeLabel): void
    {
        $cart = $this->customerCart();
        $pricing = app(CheckoutPricingService::class)->calculate($cart);
        $engine = app(CheckoutRulesEngine::class)->calculate($cart);

        $this->assertEquals($expectedShipping, $pricing['shipping']);
        $this->assertEquals($expectedShipping, $engine['shipping']);
        $this->assertEquals($pricing['shipping'], $engine['shipping']);

        $formatted = number_format($expectedShipping, 2);

        $cartResponse = $this->actingAs($this->customer)->get(route('shop.cart.index'));
        $cartResponse->assertOk();
        if ($expectFreeLabel) {
            $cartResponse->assertSee('order-summary__free', false);
        } else {
            $cartResponse->assertSee($formatted, false);
            $cartResponse->assertDontSee('order-summary__free', false);
        }

        $checkoutResponse = $this->actingAs($this->customer)->get(route('shop.checkout.index'));
        $checkoutResponse->assertOk()->assertSee('Order summary', false);
        if ($expectFreeLabel) {
            $checkoutResponse->assertSee('order-summary__free', false);
        } else {
            $checkoutResponse->assertSee($formatted, false);
            $checkoutResponse->assertDontSee('order-summary__free', false);
        }
    }

    private function placeOrder(): Order
    {
        $this->actingAs($this->customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $this->customer->name,
                'customer_email' => $this->customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'billing_same_as_shipping' => '1',
                'shipping_line1' => 'Verification Street 1',
                'shipping_city' => 'Lahore',
                'shipping_country' => 'Pakistan',
            ])
            ->assertRedirect();

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);

        return $order->fresh(['invoice']);
    }

    private function assertPersistedShipping(Order $order, float $expectedShipping): void
    {
        $this->assertEquals($expectedShipping, (float) $order->shipping_total);

        $success = $this->actingAs($this->customer)
            ->get(route('shop.checkout.success', $order));
        $success->assertOk();
        if ($expectedShipping > 0) {
            $success->assertSee(number_format($expectedShipping, 2), false);
        }

        $history = $this->actingAs($this->customer)
            ->get(route('shop.account.orders.show', $order));
        $history->assertOk();
        if ($expectedShipping > 0) {
            $history->assertSee(number_format($expectedShipping, 2), false);
        } else {
            $history->assertSee(number_format(0, 2), false);
        }

        $dashboard = $this->actingAs($this->customer)
            ->get(route('shop.account.dashboard'));
        $dashboard->assertOk()->assertSee($order->order_number, false);
        $dashboard->assertSee(number_format((float) $order->grand_total, 2), false);

        $erp = $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order));
        $erp->assertOk();
        $erp->assertSee(number_format($expectedShipping, 2), false);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);
        $this->assertEquals($expectedShipping, (float) $invoice->shipping_total);
        $this->assertEquals((float) $order->shipping_total, (float) $invoice->shipping_total);
        $this->assertEquals((float) $order->grand_total, (float) $invoice->total);

        if ($expectedShipping > 0) {
            $this->actingAs($this->admin)
                ->get(route('admin.orders.invoice.print', $order))
                ->assertOk()
                ->assertSee(number_format($expectedShipping, 2), false);
        } else {
            $this->actingAs($this->admin)
                ->get(route('admin.orders.invoice.print', $order))
                ->assertOk()
                ->assertSee(number_format(0, 2), false);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee(number_format($expectedShipping, 2), false);
    }

    private function attachProductToCategory(Category $category, ?int $excludeId = null): Product
    {
        $query = Product::query()->active()->with('defaultVariant');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        $product = $query->firstOrFail();
        $product->categories()->syncWithoutDetaching([$category->id]);

        return $product->fresh(['defaultVariant', 'categories']);
    }
}
