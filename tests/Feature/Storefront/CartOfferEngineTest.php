<?php

namespace Tests\Feature\Storefront;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPipeLengthOption;
use App\Services\CartService;
use App\Services\CheckoutRulesEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartOfferEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_selected_offer_applies_discount_and_can_waive_shipping(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();
        $this->assertNotNull($product);
        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);

        $product->update(['offers_enabled' => true]);

        $offer = ProductOffer::query()->create([
            'product_id' => $product->id,
            'buy_quantity' => 3,
            'discount_percent' => 15,
            'free_shipping' => true,
            'sort_order' => 0,
        ]);

        $this->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'product_offer_id' => $offer->id,
        ])->assertRedirect(route('shop.cart.index'));

        $item = CartItem::query()->first();
        $this->assertNotNull($item);
        $this->assertSame($offer->id, $item->product_offer_id);
        $this->assertSame(3, $item->quantity);

        $cart = app(CartService::class)->getCart();
        $pricing = app(CheckoutRulesEngine::class)->calculate($cart);

        $lineTotal = (float) $item->unit_price * 3;
        $expectedDiscount = round($lineTotal * 0.15, 2);

        $this->assertEquals($expectedDiscount, $pricing['discount']);
        $this->assertSame(0.0, $pricing['shipping']);
        $this->assertTrue($pricing['qualifies_for_free_shipping']);
    }

    public function test_pipe_length_adds_to_unit_price_and_updates_via_ajax(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();
        $this->assertNotNull($product);
        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);

        $basePrice = (float) app(\App\Services\ProductPricingService::class)->displayPrice($variant);

        $product->update([
            'pipe_length_enabled' => true,
            'option_title' => 'Size',
        ]);

        $pipe = ProductPipeLengthOption::query()->create([
            'product_id' => $product->id,
            'label' => '3 meter',
            'additional_price' => 250,
            'sort_order' => 0,
        ]);

        $this->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'pipe_length_option_id' => $pipe->id,
        ])->assertRedirect(route('shop.cart.index'));

        $item = CartItem::query()->first();
        $this->assertNotNull($item);
        $this->assertSame($pipe->id, $item->pipe_length_option_id);
        $this->assertEquals(round($basePrice + 250, 2), (float) $item->unit_price);

        $pipeLonger = ProductPipeLengthOption::query()->create([
            'product_id' => $product->id,
            'label' => '6 meter',
            'additional_price' => 500,
            'sort_order' => 1,
        ]);

        $response = $this->patchJson(route('shop.cart.update', $item), [
            'quantity' => 1,
            'pipe_length_option_id' => $pipeLonger->id,
        ])->assertOk();

        $this->assertEquals(
            round($basePrice + 500, 2),
            (float) $response->json('items.0.unit_price')
        );
    }

    public function test_changing_offer_in_cart_recalculates_discount(): void
    {
        $product = Product::query()->active()->with('defaultVariant')->first();
        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);

        $product->update(['offers_enabled' => true]);

        $offer = ProductOffer::query()->create([
            'product_id' => $product->id,
            'buy_quantity' => 2,
            'discount_percent' => 10,
            'free_shipping' => false,
            'sort_order' => 0,
        ]);

        $this->post(route('shop.cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect();

        $item = CartItem::query()->first();
        $this->assertNotNull($item);

        $response = $this->patchJson(route('shop.cart.update', $item), [
            'quantity' => 2,
            'product_offer_id' => $offer->id,
        ])->assertOk();

        $lineTotal = (float) $item->fresh()->unit_price * 2;
        $expectedDiscount = round($lineTotal * 0.10, 2);

        $this->assertEquals($expectedDiscount, (float) $response->json('totals.discount'));
    }
}
