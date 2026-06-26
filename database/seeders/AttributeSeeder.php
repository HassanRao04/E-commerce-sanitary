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
                'values' => ['Chrome', 'Matte Black', 'Brushed Nickel', 'Gold', 'White'],
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
                'values' => ['White', 'Black', 'Grey', 'Beige'],
            ],
        ];

        foreach ($definitions as $index => $definition) {
            $attribute = Attribute::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'type' => 'select',
                    'is_filterable' => $definition['is_filterable'],
                    'is_variant_attribute' => $definition['is_variant_attribute'],
                    'sort_order' => $index,
                ]
            );

            foreach ($definition['values'] as $valueIndex => $value) {
                AttributeValue::updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'slug' => Str::slug($value),
                    ],
                    [
                        'value' => $value,
                        'sort_order' => $valueIndex,
                    ]
                );
            }
        }
    }
}
