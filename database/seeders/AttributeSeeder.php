<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'name' => 'Finish',
                'slug' => 'finish',
                'is_filterable' => true,
                'is_variant_attribute' => true,
                'values' => ['Chrome', 'Matte Black', 'Brushed Nickel', 'Gold', 'White', 'Gun Grey'],
            ],
            [
                'name' => 'Material',
                'slug' => 'material',
                'is_filterable' => true,
                'is_variant_attribute' => false,
                'values' => ['Brass', 'Stainless Steel', 'Ceramic', 'ABS', 'Vitreous China'],
            ],
            [
                'name' => 'Size',
                'slug' => 'size',
                'is_filterable' => true,
                'is_variant_attribute' => true,
                'values' => ['Small', 'Medium', 'Large', '600mm', '800mm'],
            ],
            [
                'name' => 'Installation Type',
                'slug' => 'installation-type',
                'is_filterable' => true,
                'is_variant_attribute' => false,
                'values' => ['Wall Mounted', 'Floor Standing', 'Counter Top', 'Concealed'],
            ],
            [
                'name' => 'Color',
                'slug' => 'color',
                'is_filterable' => true,
                'is_variant_attribute' => true,
                'values' => ['Chrome', 'Matte Black', 'White', 'Gold', 'Gun Grey'],
            ],
        ];

        foreach ($definitions as $index => $definition) {
            $attribute = Attribute::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'type' => $definition['slug'] === 'color' ? 'color' : 'select',
                    'is_filterable' => $definition['is_filterable'],
                    'is_variant_attribute' => $definition['is_variant_attribute'],
                    'sort_order' => $index,
                ]
            );

            $colorHexMap = [
                'chrome' => '#C0C0C0',
                'matte-black' => '#1A1A1A',
                'white' => '#FFFFFF',
                'gold' => '#D4AF37',
                'gun-grey' => '#4A4E53',
                'black' => '#000000',
                'grey' => '#808080',
                'beige' => '#F5F5DC',
            ];

            foreach ($definition['values'] as $valueIndex => $value) {
                $valueSlug = Str::slug($value);

                AttributeValue::updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'slug' => $valueSlug,
                    ],
                    [
                        'value' => $value,
                        'color_hex' => in_array($definition['slug'], ['color', 'finish'], true)
                            ? ($colorHexMap[$valueSlug] ?? null)
                            : null,
                        'sort_order' => $valueIndex,
                    ]
                );
            }
        }
    }
}
