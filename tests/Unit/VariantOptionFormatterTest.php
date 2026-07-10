<?php

namespace Tests\Unit;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\VariantOptionFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantOptionFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_formats_color_size_and_material_in_priority_order(): void
    {
        $product = Product::factory()->create(['product_type' => 'variable']);

        $color = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_variant_attribute' => true,
        ]);

        $size = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'is_variant_attribute' => true,
        ]);

        $material = Attribute::create([
            'name' => 'Material',
            'slug' => 'material',
            'type' => 'select',
            'is_variant_attribute' => true,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'FMT-001',
            'variant_name' => 'Chrome / 24 Inch / Ceramic',
            'price' => 1000,
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $variant->attributeValues()->createMany([
            ['attribute_id' => $material->id, 'attribute_value_id' => AttributeValue::create([
                'attribute_id' => $material->id,
                'value' => 'Ceramic',
                'slug' => 'ceramic',
            ])->id],
            ['attribute_id' => $size->id, 'attribute_value_id' => AttributeValue::create([
                'attribute_id' => $size->id,
                'value' => '24 Inch',
                'slug' => '24-inch',
            ])->id],
            ['attribute_id' => $color->id, 'attribute_value_id' => AttributeValue::create([
                'attribute_id' => $color->id,
                'value' => 'Chrome',
                'slug' => 'chrome',
                'color_hex' => '#C0C0C0',
            ])->id],
        ]);

        $options = VariantOptionFormatter::forVariant($variant->fresh(['attributeValues.attribute', 'attributeValues.attributeValue']));

        $this->assertSame(['color', 'size', 'material'], collect($options)->pluck('slug')->all());
        $this->assertSame('Color: Chrome · Size: 24 Inch · Material: Ceramic', VariantOptionFormatter::label($options));
    }
}
