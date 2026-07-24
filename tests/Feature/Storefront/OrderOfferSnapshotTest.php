<?php

namespace Tests\Feature\Storefront;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPipeLengthOption;
use App\Models\User;
use App\Services\Admin\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderOfferSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_checkout_snapshots_offer_pipe_and_shipping_discount(): void
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
            'option_title' => 'Cable Length',
        ]);

        $offer = ProductOffer::query()->create([
            'product_id' => $product->id,
            'buy_quantity' => 3,
            'discount_percent' => 15,
            'free_shipping' => true,
            'sort_order' => 0,
        ]);

        $pipe = ProductPipeLengthOption::query()->create([
            'product_id' => $product->id,
            'label' => '4 meter',
            'additional_price' => 200,
            'sort_order' => 0,
        ]);

        $this->actingAs($customer)
            ->post(route('shop.cart.store'), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 3,
                'product_offer_id' => $offer->id,
                'pipe_length_option_id' => $pipe->id,
            ])
            ->assertRedirect();

        $this->actingAs($customer)
            ->from(route('shop.checkout.index'))
            ->post(route('shop.checkout.store'), [
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '03001234567',
                'payment_method' => PaymentMethod::Cod->value,
                'billing_same_as_shipping' => '1',
                'shipping_line1' => '123 Test Street',
                'shipping_city' => 'Lahore',
                'shipping_country' => 'Pakistan',
            ])
            ->assertRedirect();

        $order = Order::query()->find(session('shop.last_order_id'));
        $this->assertNotNull($order);
        $this->assertGreaterThan(0, (float) $order->offer_discount_total);
        $this->assertGreaterThan(0, (float) $order->shipping_discount_total);
        $this->assertSame(0.0, (float) $order->shipping_total);

        $item = $order->items()->first();
        $this->assertNotNull($item);
        $this->assertSame('Buy 3 · 15% OFF · Free Shipping', $item->selected_offer);
        $this->assertSame('Cable Length', $item->option_title);
        $this->assertSame('4 meter', $item->pipe_length);
        $this->assertEquals(200.0, (float) $item->pipe_extra_cost);
        $this->assertEquals(15.0, (float) $item->discount_percent);
        $this->assertGreaterThan(0, (float) $item->discount_amount);
        $this->assertEquals(
            round((float) $item->unit_price - 200, 2),
            (float) $item->original_unit_price
        );

        $invoice = app(InvoiceService::class)->generateFromOrder($order->fresh('items'));
        $this->assertEquals((float) $order->offer_discount_total, (float) $invoice->offer_discount_total);
        $this->assertEquals((float) $order->shipping_discount_total, (float) $invoice->shipping_discount_total);

        $invoiceItem = $invoice->items()->first();
        $this->assertSame($item->selected_offer, $invoiceItem->selected_offer);
        $this->assertSame($item->pipe_length, $invoiceItem->pipe_length);
        $this->assertEquals((float) $item->pipe_extra_cost, (float) $invoiceItem->pipe_extra_cost);
        $this->assertEquals((float) $item->discount_amount, (float) $invoiceItem->discount_amount);
    }
}
