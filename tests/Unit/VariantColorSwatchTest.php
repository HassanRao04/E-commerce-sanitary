<?php

namespace Tests\Unit;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Support\VariantColorSwatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantColorSwatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_hex_accepts_hash_prefix(): void
    {
        $this->assertSame('#000000', VariantColorSwatch::normalizeHex('000000'));
        $this->assertSame('#FFFFFF', VariantColorSwatch::normalizeHex('#ffffff'));
        $this->assertNull(VariantColorSwatch::normalizeHex('invalid'));
    }

    public function test_for_variant_prefers_attribute_value_hex(): void
    {
        $attribute = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_filterable' => true,
            'is_variant_attribute' => true,
        ]);

        $value = AttributeValue::create([
            'attribute_id' => $attribute->id,
            'value' => 'Gold',
            'slug' => 'gold',
            'color_hex' => '#D4AF37',
        ]);

        $variant = ProductVariant::factory()->create([
            'color_name' => 'Gold',
            'color_hex' => '#111111',
        ]);

        $variant->attributeValues()->create([
            'attribute_id' => $attribute->id,
            'attribute_value_id' => $value->id,
        ]);

        $swatch = VariantColorSwatch::forVariant($variant);

        $this->assertSame('Gold', $swatch['name']);
        $this->assertSame('#D4AF37', $swatch['hex']);
    }
}
