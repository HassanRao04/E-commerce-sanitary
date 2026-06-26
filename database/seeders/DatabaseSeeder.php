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
            InventorySeeder::class,
            DemoCustomerSeeder::class,
            OrderSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
