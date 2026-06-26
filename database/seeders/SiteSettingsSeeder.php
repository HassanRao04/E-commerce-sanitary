<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Sanitary Store ERP',
                'email' => config('shop.admin_email'),
                'contact_phone' => '+92-300-0000000',
                'whatsapp' => '+92-300-0000000',
                'address' => 'Karachi, Pakistan',
                'default_meta_description' => 'Premium sanitary ware and bathroom fittings.',
                'currency' => config('shop.currency'),
                'tax_rate' => config('shop.tax_rate'),
                'shipping_flat_rate' => config('shop.shipping_flat_rate'),
                'social_links' => [
                    'facebook' => null,
                    'instagram' => null,
                    'youtube' => null,
                ],
            ]
        );
    }
}
