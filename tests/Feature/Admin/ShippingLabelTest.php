<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingLabelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_enhanced_a4_label_includes_required_fields(): void
    {
        $shipment = $this->shipmentWithDetails();

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', ['shipping' => $shipment, 'format' => 'a4']))
            ->assertOk()
            ->assertSee($shipment->courier_name, false)
            ->assertSee($shipment->order->order_number, false)
            ->assertSee($shipment->order->customer_name, false)
            ->assertSee($shipment->tracking_number, false)
            ->assertSee('Ship To', false)
            ->assertSee('Products', false)
            ->assertSee('4×6 Thermal', false)
            ->assertSee('shipping-label--a4', false)
            ->assertSee('Order Amount', false)
            ->assertSee('COD To Collect', false)
            ->assertSee('Payment Method', false)
            ->assertSee('Payment Status', false)
            ->assertSee('Shipment Date:', false)
            ->assertSee('Barcode', false)
            ->assertSee('QR Code', false)
            ->assertDontSee('Subtotal', false)
            ->assertDontSee('Coupon Discount', false)
            ->assertDontSee('Grand Total', false);
    }

    public function test_label_shows_cod_amount_to_collect_for_cod_orders(): void
    {
        $shipment = $this->shipmentWithDetails();
        $order = $shipment->order;
        $order->update([
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'grand_total' => 5500,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', ['shipping' => $shipment->fresh(), 'format' => 'a4']))
            ->assertOk()
            ->assertSee('COD To Collect', false)
            ->assertSee('5,500.00', false);
    }

    public function test_label_shows_zero_cod_amount_for_non_cod_orders(): void
    {
        $shipment = $this->shipmentWithDetails();
        $shipment->order->update([
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', ['shipping' => $shipment->fresh(), 'format' => 'a4']))
            ->assertOk()
            ->assertSee('COD To Collect', false)
            ->assertSee('0.00', false);
    }

    public function test_thermal_label_format_is_supported(): void
    {
        $shipment = $this->shipmentWithDetails();

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', ['shipping' => $shipment, 'format' => 'thermal']))
            ->assertOk()
            ->assertSee('shipping-label--thermal', false)
            ->assertSee($shipment->order->items->first()->product_name, false);
    }

    public function test_existing_print_label_route_still_works_without_format(): void
    {
        $shipment = Shipping::first();

        if (! $shipment) {
            $order = Order::first();
            $this->actingAs($this->admin)->post(route('admin.orders.shipping.store', $order), [
                'courier_name' => 'TCS',
                'tracking_number' => 'TCS123456',
                'status' => 'pending',
            ]);
            $shipment = $order->fresh()->shipments()->first();
        }

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', $shipment))
            ->assertOk()
            ->assertSee($shipment->courier_name, false)
            ->assertSee('Print', false);
    }

    public function test_label_includes_barcode_and_qr_scripts(): void
    {
        $shipment = $this->shipmentWithDetails();

        $this->actingAs($this->admin)
            ->get(route('admin.shipping.label', ['shipping' => $shipment, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('shipping-label-barcode', false)
            ->assertSee('shipping-label-qrcode', false)
            ->assertSee('JsBarcode', false)
            ->assertSee('QRCode', false);
    }

    private function shipmentWithDetails(): Shipping
    {
        $order = Order::query()->with('items')->firstOrFail();
        $order->shipments()->delete();

        return Shipping::create([
            'order_id' => $order->id,
            'courier_name' => 'TCS',
            'tracking_number' => 'TCSLABEL999',
            'awb_number' => 'AWBLABEL999',
            'status' => 'pending',
            'booking_status' => 'booked',
            'booked_at' => now(),
        ])->fresh(['order.items', 'order.shippingAddress']);
    }
}
