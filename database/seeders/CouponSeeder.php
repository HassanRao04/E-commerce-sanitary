<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'type' => CouponType::Percent,
                'value' => 10,
                'min_order_amount' => 5000,
                'max_uses' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'FLAT500',
                'type' => CouponType::Fixed,
                'value' => 500,
                'min_order_amount' => 3000,
                'max_uses' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'PROJECT15',
                'type' => CouponType::Percent,
                'value' => 15,
                'min_order_amount' => 25000,
                'max_uses' => null,
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->updateOrCreate(
                ['code' => $coupon['code']],
                $coupon,
            );
        }
    }
}
