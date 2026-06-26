<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 50000);
        $shipping = (float) config('shop.shipping_flat_rate', 500);
        $tax = round($subtotal * ((float) config('shop.tax_rate', 0) / 100), 2);

        return [
            'order_number' => 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'tracking_token' => Str::random(32),
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '+92-300-'.fake()->numerify('#######'),
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => PaymentMethod::Cod,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_total' => $shipping,
            'tax_total' => $tax,
            'grand_total' => $subtotal + $shipping + $tax,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Paid,
            'status' => OrderStatus::Confirmed,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (): array => ['user_id' => null]);
    }
}
