<?php

namespace Tests\Unit;

use App\Enums\ShippingMethod;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CategoryShippingRate;
use App\Models\Product;
use App\Models\ProductShippingRate;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Services\ShippingCalculatorService;
use App\Services\ShippingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_flat_rate_shipping_is_applied_by_default(): void
    {
        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 10000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(1000);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 1000.0);

        $this->assertEquals(300.0, $result['shipping']);
        $this->assertFalse($result['qualifies_for_free_shipping']);
        $this->assertSame('flat', $result['method']);
    }

    public function test_free_shipping_applies_when_threshold_is_met(): void
    {
        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 10000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(10500);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 10500.0);

        $this->assertEquals(0.0, $result['shipping']);
        $this->assertTrue($result['qualifies_for_free_shipping']);
        $this->assertSame('free', $result['method']);
    }

    public function test_free_shipping_does_not_override_flat_rate_below_threshold(): void
    {
        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 10000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(9999.99);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 9999.99);

        $this->assertEquals(300.0, $result['shipping']);
        $this->assertFalse($result['qualifies_for_free_shipping']);
        $this->assertSame('flat', $result['method']);
    }

    public function test_free_shipping_disabled_always_uses_selected_method(): void
    {
        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => false,
            'free_shipping_threshold' => 1000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(25000);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 25000.0);

        $this->assertEquals(300.0, $result['shipping']);
        $this->assertFalse($result['qualifies_for_free_shipping']);
        $this->assertSame('flat', $result['method']);
        $this->assertSame(0.0, $result['free_shipping_threshold']);
    }

    public function test_free_shipping_applies_at_exact_threshold(): void
    {
        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 10000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(10000);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 10000.0);

        $this->assertEquals(0.0, $result['shipping']);
        $this->assertTrue($result['qualifies_for_free_shipping']);
        $this->assertSame('free', $result['method']);
    }

    public function test_product_based_shipping_sums_all_product_rates(): void
    {
        $products = Product::query()->with('defaultVariant')->limit(3)->get();
        $this->assertGreaterThanOrEqual(3, $products->count());

        $this->configureShipping([
            'flat_rate_enabled' => false,
            'flat_rate_amount' => 300,
            'product_rate_enabled' => true,
            'free_shipping_enabled' => false,
            'default_method' => ShippingMethod::Product,
        ]);

        $amounts = [200, 600, 500];
        foreach ($products as $index => $product) {
            ProductShippingRate::updateOrCreate(
                ['product_id' => $product->id],
                ['amount' => $amounts[$index], 'free_shipping' => false, 'is_active' => true],
            );
        }

        app(ShippingSettingsService::class)->clearCache();

        $user = User::factory()->create();
        $user->assignRole('customer');
        $cart = Cart::query()->create(['user_id' => $user->id]);

        foreach ($products as $product) {
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
                'unit_price' => $product->defaultVariant->price,
            ]);
        }

        $cart = $cart->fresh(['items.product.categories']);
        $result = app(ShippingCalculatorService::class)->calculate($cart, 3000.0);

        $this->assertEquals(1300.0, $result['shipping']);
        $this->assertSame('product', $result['method']);
    }

    public function test_product_free_shipping_applies_when_all_cart_products_qualify(): void
    {
        $products = Product::query()->with('defaultVariant')->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $products->count());

        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'product_rate_enabled' => true,
            'free_shipping_enabled' => false,
            'default_method' => ShippingMethod::Flat,
        ]);

        foreach ($products as $product) {
            ProductShippingRate::updateOrCreate(
                ['product_id' => $product->id],
                ['amount' => 200, 'free_shipping' => true, 'is_active' => true],
            );
        }

        app(ShippingSettingsService::class)->clearCache();

        $user = User::factory()->create();
        $user->assignRole('customer');
        $cart = Cart::query()->create(['user_id' => $user->id]);

        foreach ($products as $product) {
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
                'unit_price' => $product->defaultVariant->price,
            ]);
        }

        $result = app(ShippingCalculatorService::class)->calculate(
            $cart->fresh(['items.product.categories']),
            1500.0,
        );

        $this->assertEquals(0.0, $result['shipping']);
        $this->assertTrue($result['qualifies_for_free_shipping']);
        $this->assertSame('free_product', $result['method']);
    }

    public function test_product_free_shipping_requires_all_cart_products(): void
    {
        $products = Product::query()->with('defaultVariant')->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $products->count());

        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 300,
            'free_shipping_enabled' => false,
            'default_method' => ShippingMethod::Flat,
        ]);

        ProductShippingRate::updateOrCreate(
            ['product_id' => $products[0]->id],
            ['amount' => 200, 'free_shipping' => true, 'is_active' => true],
        );
        ProductShippingRate::updateOrCreate(
            ['product_id' => $products[1]->id],
            ['amount' => 600, 'free_shipping' => false, 'is_active' => true],
        );

        app(ShippingSettingsService::class)->clearCache();

        $user = User::factory()->create();
        $user->assignRole('customer');
        $cart = Cart::query()->create(['user_id' => $user->id]);

        foreach ($products as $product) {
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $product->defaultVariant->id,
                'quantity' => 1,
                'unit_price' => $product->defaultVariant->price,
            ]);
        }

        $result = app(ShippingCalculatorService::class)->calculate(
            $cart->fresh(['items.product.categories']),
            1500.0,
        );

        $this->assertEquals(300.0, $result['shipping']);
        $this->assertFalse($result['qualifies_for_free_shipping']);
        $this->assertSame('flat', $result['method']);
    }

    public function test_product_based_shipping_uses_product_rate(): void
    {
        $product = Product::query()->first();

        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 100,
            'product_rate_enabled' => true,
            'free_shipping_enabled' => false,
            'default_method' => ShippingMethod::Product,
        ]);

        ProductShippingRate::updateOrCreate(
            ['product_id' => $product->id],
            ['amount' => 350, 'free_shipping' => false, 'is_active' => true],
        );

        app(ShippingSettingsService::class)->clearCache();

        $cart = $this->makeCartWithProduct($product, 2);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 2000.0);

        $this->assertEquals(700.0, $result['shipping']);
        $this->assertSame('product', $result['method']);
    }

    public function test_category_based_shipping_uses_category_rate(): void
    {
        $product = Product::query()->with('categories')->first();
        $category = $product->categories->first() ?? Category::query()->first();

        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 100,
            'category_rate_enabled' => true,
            'free_shipping_enabled' => false,
            'default_method' => ShippingMethod::Category,
        ]);

        CategoryShippingRate::updateOrCreate(
            ['category_id' => $category->id],
            ['amount' => 200, 'is_active' => true],
        );

        app(ShippingSettingsService::class)->clearCache();

        $cart = $this->makeCartWithProduct($product, 1);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 1500.0);

        $this->assertEquals(200.0, $result['shipping']);
        $this->assertSame('category', $result['method']);
    }

    /** @param  array<string, mixed>  $attributes */
    private function configureShipping(array $attributes): void
    {
        ShippingSetting::current()->update($attributes);
        app(ShippingSettingsService::class)->clearCache();
    }

    private function makeCartWithSubtotal(float $subtotal): Cart
    {
        $product = Product::query()->with('defaultVariant')->first();
        $quantity = max(1, (int) ceil($subtotal / max(1, (float) $product->defaultVariant->price)));

        return $this->makeCartWithProduct($product, $quantity);
    }

    private function makeCartWithProduct(Product $product, int $quantity): Cart
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $cart = Cart::query()->create(['user_id' => $user->id]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'quantity' => $quantity,
            'unit_price' => $product->defaultVariant->price,
        ]);

        return $cart->fresh(['items.product.categories']);
    }
}
