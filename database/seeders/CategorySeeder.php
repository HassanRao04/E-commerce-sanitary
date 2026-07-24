<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Preserve prior "Accessories" root by renaming into Bathroom Accessories.
        Category::query()
            ->where('slug', 'accessories')
            ->whereNull('parent_id')
            ->update([
                'name' => 'Bathroom Accessories',
                'slug' => 'bathroom-accessories',
                'description' => 'Mirrors, dispensers, shelves, drains, and bathroom fittings.',
                'is_active' => true,
            ]);

        $tree = [
            'Basins & Sinks' => [
                'Wash Basins' => 'Ceramic and modern wash basins for bathrooms and powder rooms.',
                'Counter Top Basins' => 'Stylish basins designed for vanity and counter-top installation.',
                'Wall Hung Basins' => 'Space-saving wall-mounted basins for compact bathrooms.',
                'Pedestal Basins' => 'Classic pedestal basins for traditional bathroom layouts.',
                'Kitchen Sinks' => 'Single and double bowl kitchen sinks for everyday cooking spaces.',
            ],
            'Faucets & Mixers' => [
                'Bathroom Faucets' => 'Basin mixers and bathroom taps for wash areas.',
                'Kitchen Faucets' => 'Kitchen mixers and sink faucets for cooking and cleaning.',
                'Basin Mixers' => 'Single-lever and dual-handle basin mixers.',
                'Kitchen Mixers' => 'Pull-out and standard kitchen mixer taps.',
                'Shower Mixers' => 'Thermostatic and manual shower mixer valves.',
                'Health Faucets' => 'Bidet sprays and health faucets for washroom hygiene.',
                'Angle Valves' => 'Chrome and brass angle valves for fixture connections.',
            ],
            'Showers' => [
                'Shower Sets' => 'Complete shower sets with head, hand shower, and rail.',
                'Rain Showers' => 'Overhead rain shower heads for spa-like bathing.',
                'Hand Showers' => 'Handheld showers and slides for flexible bathing.',
                'Shower Panels' => 'Multi-jet shower panels for modern wet rooms.',
            ],
            'Toilets' => [
                'One Piece' => 'One-piece toilet suites with seamless ceramic design.',
                'Two Piece' => 'Separate cistern and pan toilet sets.',
                'Wall Hung' => 'Wall-hung toilets with concealed cistern support.',
                'Toilet Seats' => 'Soft-close and standard toilet seats in multiple finishes.',
            ],
            'Bathroom Accessories' => [
                'Mirrors' => 'Bathroom mirrors and LED vanity mirrors.',
                'Soap Dispensers' => 'Wall-mounted and counter soap dispensers.',
                'Towel Rails' => 'Towel rails, rings, and warmers for bathrooms.',
                'Corner Shelves' => 'Corner shelves and niche storage for wet areas.',
                'Floor Drains' => 'Floor drains and waste outlets for bathrooms.',
                'Flexible Pipes' => 'Flexible connector pipes for taps and sanitary fittings.',
            ],
            'Kitchen Accessories' => [
                'Kitchen Soap Dispensers' => 'Kitchen soap dispensers for sink-side convenience.',
                'Kitchen Corner Shelves' => 'Kitchen corner shelves and organizers.',
                'Kitchen Flexible Pipes' => 'Flexible hoses for kitchen faucet connections.',
            ],
        ];

        $rootOrder = 0;

        foreach ($tree as $rootName => $children) {
            $root = Category::updateOrCreate(
                ['slug' => Str::slug($rootName)],
                [
                    'name' => $rootName,
                    'description' => $this->rootDescription($rootName),
                    'image' => null,
                    'is_active' => true,
                    'sort_order' => $rootOrder++,
                ]
            );

            if (! $root->wasRecentlyCreated && $root->parent_id !== null) {
                $root->saveAsRoot();
            }

            $childOrder = 0;

            foreach ($children as $childName => $description) {
                $root->children()->updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'description' => $description,
                        'image' => null,
                        'is_active' => true,
                        'sort_order' => $childOrder++,
                    ]
                );
            }
        }
    }

    private function rootDescription(string $rootName): string
    {
        return match ($rootName) {
            'Basins & Sinks' => 'Wash basins, kitchen sinks, and related ceramic fixtures.',
            'Faucets & Mixers' => 'Bathroom and kitchen faucets, mixers, valves, and health faucets.',
            'Showers' => 'Shower sets, rain showers, hand showers, and shower panels.',
            'Toilets' => 'Toilet suites and toilet seats for residential and commercial use.',
            'Bathroom Accessories' => 'Mirrors, dispensers, shelves, drains, and bathroom fittings.',
            'Kitchen Accessories' => 'Practical kitchen sink accessories and supporting fittings.',
            default => 'Sanitary ware category for storefront browsing.',
        };
    }
}
