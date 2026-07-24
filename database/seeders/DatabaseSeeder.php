<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SiteSettingsSeeder::class,
            AdminUserSeeder::class,
            WarehouseSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            ProductImageSeeder::class,
            InventorySeeder::class,
            FeaturedProductSeeder::class,
            BestSellerProductSeeder::class,
            ProductVariationSeeder::class,
            ShippingSettingsSeeder::class,
            TaxChargeSettingsSeeder::class,
            CheckoutRulesSettingsSeeder::class,
            ReviewSettingsSeeder::class,
            OrderStatusSeeder::class,
            DemoCustomerSeeder::class,
            CustomerProfileSeeder::class,
            OrderSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
