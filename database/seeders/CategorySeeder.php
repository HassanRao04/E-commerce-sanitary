<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Basins & Sinks' => ['Counter Top Basins', 'Wall Hung Basins', 'Pedestal Basins'],
            'Faucets & Mixers' => ['Basin Mixers', 'Kitchen Mixers', 'Shower Mixers'],
            'Showers' => ['Rain Showers', 'Hand Showers', 'Shower Panels'],
            'Toilets' => ['One Piece', 'Two Piece', 'Wall Hung'],
            'Accessories' => ['Towel Rails', 'Soap Dispensers', 'Mirrors'],
        ];

        foreach ($tree as $rootName => $children) {
            $root = Category::updateOrCreate(
                ['slug' => Str::slug($rootName)],
                ['name' => $rootName, 'is_active' => true, 'sort_order' => 0]
            );

            if (! $root->wasRecentlyCreated && $root->parent_id !== null) {
                $root->saveAsRoot();
            }

            foreach ($children as $index => $childName) {
                $root->children()->updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
