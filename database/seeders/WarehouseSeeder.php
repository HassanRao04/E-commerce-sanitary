<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'address' => 'Industrial Area, Karachi',
                'city' => 'Karachi',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Warehouse::updateOrCreate(
            ['code' => 'LHR'],
            [
                'name' => 'Lahore Distribution Center',
                'address' => 'Manga Mandi, Lahore',
                'city' => 'Lahore',
                'is_default' => false,
                'is_active' => true,
            ]
        );
    }
}
