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
            'flat_rate_amount' => 100,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 5000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(1000);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 1000.0);

        $this->assertEquals(100.0, $result['shipping']);
        $this->assertFalse($result['qualifies_for_free_shipping']);
        $this->assertSame('flat', $result['method']);
    }

    public function test_free_shipping_applies_when_threshold_is_met(): void
    {
        $this->configureShipping([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 100,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 5000,
            'default_method' => ShippingMethod::Flat,
        ]);

        $cart = $this->makeCartWithSubtotal(5200);

        $result = app(ShippingCalculatorService::class)->calculate($cart, 5200.0);

        $this->assertEquals(0.0, $result['shipping']);
        $this->assertTrue($result['qualifies_for_free_shipping']);
        $this->assertSame('free', $result['method']);
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
            ['amount' => 350, 'is_active' => true],
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
