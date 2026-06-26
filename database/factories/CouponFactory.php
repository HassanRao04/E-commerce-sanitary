<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('COUPON-####')),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_order_amount' => 1000,
            'max_uses' => 100,
            'is_active' => true,
        ];
    }
}
