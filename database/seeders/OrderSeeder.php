<?php

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\Shipping;
use App\Models\Tracking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::with('user')->first();
        $variant = ProductVariant::with('product')->first();

        if (! $customer || ! $variant) {
            return;
        }

        $address = Address::firstOrCreate(
            ['user_id' => $customer->user_id, 'line1' => '123 Main Boulevard'],
            [
                'type' => AddressType::Shipping->value,
                'full_name' => $customer->user->name,
                'phone' => $customer->user->phone ?? '+92-300-0000000',
                'city' => 'Karachi',
                'country' => 'Pakistan',
                'is_default' => true,
            ]
        );

        $orders = [
            ['status' => 'pending', 'payment' => PaymentStatus::Pending, 'number' => 'ORD-'.now()->format('Ymd').'-0001'],
            ['status' => 'processing', 'payment' => PaymentStatus::Paid, 'number' => 'ORD-'.now()->format('Ymd').'-0002'],
            ['status' => 'shipped', 'payment' => PaymentStatus::Paid, 'number' => 'ORD-'.now()->format('Ymd').'-0003', 'ship' => true],
        ];

        foreach ($orders as $index => $data) {
            $qty = $index + 1;
            $unitPrice = (float) $variant->price;
            $subtotal = $unitPrice * $qty;
            $shipping = (float) config('shop.shipping_flat_rate', 500);

            $order = Order::updateOrCreate(
                ['order_number' => $data['number']],
                [
                    'user_id' => $customer->user_id,
                    'customer_name' => $customer->user->name,
                    'customer_email' => $customer->user->email,
                    'customer_phone' => $customer->user->phone,
                    'billing_address_id' => $address->id,
                    'shipping_address_id' => $address->id,
                    'status' => $data['status'],
                    'payment_status' => $data['payment'],
                    'payment_method' => PaymentMethod::Cod,
                    'subtotal' => $subtotal,
                    'discount_total' => 0,
                    'shipping_total' => $shipping,
                    'tax_total' => 0,
                    'grand_total' => $subtotal + $shipping,
                ]
            );

            OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'sku' => $variant->sku],
                [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->variant_name,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total' => $subtotal,
                ]
            );

            OrderStatusHistory::firstOrCreate(
                ['order_id' => $order->id, 'status' => $data['status']],
                ['note' => 'Seeded order status']
            );

            if ($data['ship'] ?? false) {
                $shipment = Shipping::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'courier_name' => 'TCS',
                        'tracking_number' => 'TCS'.Str::upper(Str::random(8)),
                        'status' => ShipmentStatus::InTransit,
                        'shipped_at' => now()->subDay(),
                    ]
                );

                Tracking::firstOrCreate(
                    ['shipment_id' => $shipment->id, 'status' => 'In Transit'],
                    [
                        'location' => 'Karachi Hub',
                        'description' => 'Package dispatched from warehouse',
                        'event_at' => now()->subHours(6),
                        'source' => 'manual',
                    ]
                );
            }
        }

        $totalSpend = Order::where('user_id', $customer->user_id)->sum('grand_total');
        $customer->update(['lifetime_spend' => $totalSpend]);
    }
}
