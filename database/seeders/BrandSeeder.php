<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Inayat Premium',
                'description' => 'Flagship sanitary ware for modern Pakistani bathrooms and project fittings.',
            ],
            [
                'name' => 'AquaFlow',
                'description' => 'Reliable faucets, mixers, and shower systems engineered for smooth water flow.',
            ],
            [
                'name' => 'Royal Sanitary',
                'description' => 'Premium basins, toilets, and bath collections with a classic royal finish.',
            ],
            [
                'name' => 'Elite Bath',
                'description' => 'Luxury bathroom suites and accessories designed for upscale interiors.',
            ],
            [
                'name' => 'Modern Kitchen',
                'description' => 'Kitchen mixers, sinks, and practical fittings for contemporary homes.',
            ],
            [
                'name' => 'HydroLux',
                'description' => 'High-performance showers and hydro-focused bath solutions.',
            ],
            [
                'name' => 'Crystal Bath',
                'description' => 'Clean ceramic designs and crystal-clear finishes for everyday bathrooms.',
            ],
            [
                'name' => 'Smart Flow',
                'description' => 'Water-efficient taps and smart bathroom fittings for modern living.',
            ],
            [
                'name' => 'Urban Sanitary',
                'description' => 'Compact sanitary ware tailored for apartments and urban renovations.',
            ],
            [
                'name' => 'EcoBath',
                'description' => 'Eco-conscious bathroom products focused on durability and water savings.',
            ],
            [
                'name' => 'Grohe',
                'description' => 'German-engineered faucets and showers trusted for precision and longevity.',
            ],
            [
                'name' => 'Kohler',
                'description' => 'International brand for toilets, basins, and complete bathroom suites.',
            ],
            [
                'name' => 'Jaquar',
                'description' => 'Comprehensive range of faucets, showers, and sanitary fittings.',
            ],
            [
                'name' => 'Master',
                'description' => 'Value-for-money sanitary fittings popular for residential projects.',
            ],
            [
                'name' => 'Falcon',
                'description' => 'Durable mixers and bath hardware suited for daily household use.',
            ],
            [
                'name' => 'Rozana',
                'description' => 'Stylish basins and sanitary pieces for contemporary washrooms.',
            ],
            [
                'name' => 'Porta',
                'description' => 'Practical sanitary ware and accessories for homes and contractors.',
            ],
            [
                'name' => 'Sonex',
                'description' => 'Widely used bathroom fittings with solid local market presence.',
            ],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'description' => $brand['description'],
                    'logo' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
