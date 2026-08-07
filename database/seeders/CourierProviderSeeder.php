<?php

namespace Database\Seeders;

use App\Models\CourierProvider;
use Illuminate\Database\Seeder;

class CourierProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Manual',
                'slug' => 'manual',
                'is_active' => true,
                'is_sandbox' => false,
                'tracking_url_template' => null,
                'sort_order' => 0,
            ],
            [
                'name' => 'TCS',
                'slug' => 'tcs',
                'is_active' => false,
                'is_sandbox' => true,
                'tracking_url_template' => 'https://www.tcsexpress.com/track/{tracking_number}',
                'sort_order' => 10,
            ],
            [
                'name' => 'Leopards Courier',
                'slug' => 'leopards',
                'is_active' => false,
                'is_sandbox' => true,
                'tracking_url_template' => 'https://www.leopardscourier.com/track/{tracking_number}',
                'sort_order' => 20,
            ],
            [
                'name' => 'M&P',
                'slug' => 'mnp',
                'is_active' => false,
                'is_sandbox' => true,
                'tracking_url_template' => null,
                'sort_order' => 30,
            ],
            [
                'name' => 'Trax',
                'slug' => 'trax',
                'is_active' => false,
                'is_sandbox' => true,
                'tracking_url_template' => null,
                'sort_order' => 40,
            ],
            [
                'name' => 'Call Courier',
                'slug' => 'call_courier',
                'is_active' => false,
                'is_sandbox' => true,
                'tracking_url_template' => null,
                'sort_order' => 50,
            ],
            [
                'name' => 'Pakistan Post',
                'slug' => 'pakistan_post',
                'is_active' => false,
                'is_sandbox' => true,
                'tracking_url_template' => 'https://www.pakpost.gov.pk/track/{tracking_number}',
                'sort_order' => 60,
            ],
        ];

        foreach ($providers as $provider) {
            CourierProvider::updateOrCreate(
                ['slug' => $provider['slug']],
                $provider,
            );
        }
    }
}
