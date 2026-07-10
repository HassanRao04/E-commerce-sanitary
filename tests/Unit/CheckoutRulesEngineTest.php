<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutRulesSetting;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\TaxChargeSetting;
use App\Services\CheckoutRulesEngine;
use App\Services\CheckoutRulesSettingsService;
use App\Services\ShippingSettingsService;
use App\Services\TaxChargeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutRulesEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_calculate_applies_erp_shipping_tax_and_coupon(): void
    {
        TaxChargeSetting::current()->update([
            'gst_enabled' => true,
            'gst_rate' => 10,
            'default_tax_type' => 'gst',
            'service_charge_enabled' => false,
            'handling_charge_enabled' => false,
        ]);
        app(TaxChargeSettingsService::class)->clearCache();

        ShippingSetting::current()->update([
            'flat_rate_enabled' => true,
            'flat_rate_amount' => 200,
            'free_shipping_enabled' => true,
            'free_shipping_threshold' => 999999,
            'default_method' => 'flat',
        ]);
        app(ShippingSettingsService::class)->clearCache();

        $product = Product::query()->active()->with('defaultVariant')->first();
        $cart = Cart::query()->create();
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'unit_price' => 6000,
            'quantity' => 1,
        ]);

        $coupon = Coupon::query()->where('code', 'WELCOME10')->first();
        $cart->update(['coupon_id' => $coupon->id]);

        $result = app(CheckoutRulesEngine::class)->calculate($cart->fresh(['items', 'coupon']));

        $this->assertEquals(6000.0, $result['subtotal']);
        $this->assertEquals(600.0, $result['discount']);
        $this->assertEquals(5400.0, $result['discounted_subtotal']);
        $this->assertEquals(200.0, $result['shipping']);
        $this->assertEquals(560.0, $result['tax']);
        $this->assertEquals(6160.0, $result['grand_total']);
        $this->assertEquals('WELCOME10', $result['coupon_code']);
        $this->assertArrayHasKey('rules', $result);
    }

    public function test_validate_for_checkout_enforces_minimum_order_amount(): void
    {
        CheckoutRulesSetting::current()->update([
            'minimum_order_enabled' => true,
            'minimum_order_amount' => 5000,
        ]);
        app(CheckoutRulesSettingsService::class)->clearCache();

        $product = Product::query()->active()->with('defaultVariant')->first();
        $cart = Cart::query()->create();
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'unit_price' => 1000,
            'quantity' => 1,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(CheckoutRulesEngine::class)->validateForCheckout($cart->fresh('items'));
    }

    public function test_discount_is_zero_when_coupons_disabled(): void
    {
        CheckoutRulesSetting::current()->update(['coupons_enabled' => false]);
        app(CheckoutRulesSettingsService::class)->clearCache();

        $product = Product::query()->active()->with('defaultVariant')->first();
        $cart = Cart::query()->create();
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant->id,
            'unit_price' => 1000,
            'quantity' => 1,
        ]);
        $cart->update(['coupon_id' => Coupon::query()->where('code', 'WELCOME10')->value('id')]);

        $result = app(CheckoutRulesEngine::class)->calculate($cart->fresh(['items', 'coupon']));

        $this->assertEquals(0.0, $result['discount']);
        $this->assertNull($result['coupon_code']);
    }
}
