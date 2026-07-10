<?php

namespace Tests\Unit;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_retail_customer_uses_sale_price_when_available(): void
    {
        $variant = $this->makeVariant([
            'price' => 10000,
            'sale_price' => 8500,
        ]);

        $quote = app(ProductPricingService::class)->forVariant($variant, CustomerType::Retail);

        $this->assertEquals(8500.0, $quote['display_price']);
        $this->assertEquals(10000.0, $quote['compare_price']);
        $this->assertSame('sale', $quote['price_type']);
    }

    public function test_wholesale_customer_uses_wholesale_price(): void
    {
        $variant = $this->makeVariant([
            'price' => 10000,
            'sale_price' => 8500,
            'wholesale_price' => 7200,
        ]);

        $quote = app(ProductPricingService::class)->forVariant($variant, CustomerType::Wholesale);

        $this->assertEquals(7200.0, $quote['display_price']);
        $this->assertSame('wholesale', $quote['price_type']);
    }

    public function test_dealer_customer_uses_dealer_price(): void
    {
        $variant = $this->makeVariant([
            'price' => 10000,
            'wholesale_price' => 7200,
            'dealer_price' => 6800,
        ]);

        $quote = app(ProductPricingService::class)->forVariant($variant, CustomerType::Dealer);

        $this->assertEquals(6800.0, $quote['display_price']);
        $this->assertSame('dealer', $quote['price_type']);
    }

    public function test_cart_sync_uses_logged_in_customer_type(): void
    {
        $user = User::query()->role('customer')->first();
        Customer::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['customer_type' => CustomerType::Wholesale],
        );

        $variant = ProductVariant::query()->first();
        $variant->update([
            'price' => 10000,
            'sale_price' => null,
            'wholesale_price' => 7500,
            'dealer_price' => null,
        ]);

        $this->actingAs($user)
            ->post(route('shop.cart.store'), [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $cartItem = $user->fresh()->cart?->items()->first()
            ?? \App\Models\Cart::query()->where('user_id', $user->id)->first()?->items()->first();

        $this->assertNotNull($cartItem);
        $this->assertEquals(7500.0, (float) $cartItem->unit_price);
    }

    /** @param  array<string, mixed>  $attributes */
    private function makeVariant(array $attributes): ProductVariant
    {
        $variant = ProductVariant::query()->first();
        $variant->update($attributes);

        return $variant->fresh();
    }
}
