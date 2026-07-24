<?php

namespace Tests\Feature\Storefront;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPipeLengthOption;
use App\Models\User;
use App\Services\Admin\InvoiceService;
use App\Services\CartService;
use App\Services\CheckoutRulesEngine;
use App\Services\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCalculationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cart_checkout_order_and_invoice_money_fields_match(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $product = Product::query()->active()->with('defaultVariant')->first();
        $this->assertNotNull($product);
        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);

        $product->update([
            'offers_enabled' => true,
            'pipe_length_enabled' => true,
            'option_title' => 'Hose Length',
        ]);

        $offer = ProductOffer::query()->create([
            'product_id' => $product->id,
            'buy_quantity' => 2,
            'discount_percent' => 10,
            'free_shipping' => true,
            'sort_order' => 0,
        ]);

        $pipe = ProductPipeLengthOption::query()->create([
            'product_id' => $product->id,
            'label' => '1.5 Meter',
            'additional_price' => 350,
            'sort_order' => 0,
        ]);

        $basePrice = app(ProductPricingService::class)->displayPrice($variant);

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'product_offer_id' => $offer->id,
                'pipe_length_option_id' => $pipe->id,
            ])
            ->assertRedirect();

        $cart = app(CartService::class)->getCart();
        $pricing = app(CheckoutRulesEngine::class)->calculate($cart);

        $expectedGrand = round(
            $pricing['discounted_subtotal']
            + $pricing['shipping']
            + $pricing['service_charge']
            + $pricing['handling_charge']
            + $pricing['tax'],
            2
        );

        $this->assertEquals($expectedGrand, $pricing['grand_total']);
        $this->assertGreaterThan(0, $pricing['offer_discount']);
        $this->assertEquals($pricing['offer_discount'], $pricing['discount']);
        $this->assertSame(0.0, $pricing['shipping']);
        $this->assertGreaterThan(0, $pricing['shipping_discount']);

        $item = $cart->items->first();
        $this->assertNotNull($item);
        $this->assertEquals(round($basePrice + 350, 2), (float) $item->unit_price);

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'billing_same_as_shipping' => '1',
                'shipping_line1' => 'Integrity Street 1',
                'shipping_city' => 'Lahore',
                'shipping_country' => 'Pakistan',
            ])
            ->assertRedirect();

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);

        $this->assertEquals($pricing['subtotal'], (float) $order->subtotal);
        $this->assertEquals($pricing['discount'], (float) $order->discount_total);
        $this->assertEquals($pricing['offer_discount'], (float) $order->offer_discount_total);
        $this->assertEquals($pricing['shipping'], (float) $order->shipping_total);
        $this->assertEquals($pricing['shipping_discount'], (float) $order->shipping_discount_total);
        $this->assertEquals($pricing['service_charge'], (float) $order->service_charge_total);
        $this->assertEquals($pricing['handling_charge'], (float) $order->handling_charge_total);
        $this->assertEquals($pricing['tax'], (float) $order->tax_total);
        $this->assertEquals($pricing['grand_total'], (float) $order->grand_total);

        $orderItem = $order->items()->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals(350.0, (float) $orderItem->pipe_extra_cost);
        $this->assertEquals(
            round((float) $orderItem->original_unit_price + (float) $orderItem->pipe_extra_cost, 2),
            (float) $orderItem->unit_price
        );
        $this->assertEquals(
            round((float) $orderItem->unit_price * $orderItem->quantity, 2),
            (float) $orderItem->total
        );
        $this->assertEquals((float) $order->offer_discount_total, (float) $orderItem->discount_amount);
        $this->assertSame('Hose Length', $orderItem->option_title);

        $reconstructed = round(
            (float) $order->subtotal
            - (float) $order->discount_total
            + (float) $order->shipping_total
            + (float) $order->service_charge_total
            + (float) $order->handling_charge_total
            + (float) $order->tax_total,
            2
        );
        $this->assertEquals((float) $order->grand_total, $reconstructed);

        $invoice = app(InvoiceService::class)->generateFromOrder($order->fresh(['items']));

        $this->assertEquals((float) $order->subtotal, (float) $invoice->subtotal);
        $this->assertEquals((float) $order->discount_total, (float) $invoice->discount_total);
        $this->assertEquals((float) $order->offer_discount_total, (float) $invoice->offer_discount_total);
        $this->assertEquals((float) $order->shipping_total, (float) $invoice->shipping_total);
        $this->assertEquals((float) $order->shipping_discount_total, (float) $invoice->shipping_discount_total);
        $this->assertEquals((float) $order->tax_total, (float) $invoice->tax_total);
        $this->assertEquals((float) $order->grand_total, (float) $invoice->total);

        $invoiceItem = $invoice->items()->first();
        $this->assertNotNull($invoiceItem);
        $this->assertEquals((float) $orderItem->unit_price, (float) $invoiceItem->unit_price);
        $this->assertEquals((float) $orderItem->discount_amount, (float) $invoiceItem->discount_amount);
        $this->assertEquals((float) $orderItem->pipe_extra_cost, (float) $invoiceItem->pipe_extra_cost);
        $this->assertSame($orderItem->option_title, $invoiceItem->option_title);
        $this->assertSame($orderItem->selected_offer, $invoiceItem->selected_offer);

        $this->actingAs($customer)
            ->get(route('shop.account.orders.show', $order))
            ->assertOk()
            ->assertSee('Offer Discount', false)
            ->assertSee('Shipping Discount', false)
            ->assertSee('Hose Length', false)
            ->assertSee(number_format((float) $order->grand_total, 2), false);

        $this->actingAs(User::where('email', config('shop.admin_email'))->first())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Offer Discount', false)
            ->assertSee(number_format((float) $order->grand_total, 2), false);
    }
}
