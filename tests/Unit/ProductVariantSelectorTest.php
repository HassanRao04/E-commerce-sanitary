<?php

namespace Tests\Unit;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductVariantSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_color_and_size_axes_from_variants(): void
    {
        $product = Product::factory()->create([
            'product_type' => 'variable',
        ]);

        $color = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_filterable' => true,
            'is_variant_attribute' => true,
        ]);

        $size = Attribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'is_filterable' => true,
            'is_variant_attribute' => true,
        ]);

        $black = AttributeValue::create([
            'attribute_id' => $color->id,
            'value' => 'Black',
            'slug' => 'black',
            'color_hex' => '#000000',
        ]);

        $white = AttributeValue::create([
            'attribute_id' => $color->id,
            'value' => 'White',
            'slug' => 'white',
            'color_hex' => '#FFFFFF',
        ]);

        $small = AttributeValue::create([
            'attribute_id' => $size->id,
            'value' => '24 Inch',
            'slug' => '24-inch',
        ]);

        $large = AttributeValue::create([
            'attribute_id' => $size->id,
            'value' => '36 Inch',
            'slug' => '36-inch',
        ]);

        $variantBlackSmall = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'P-BLK-24',
            'variant_name' => 'Black / 24 Inch',
            'price' => 1000,
            'stock_quantity' => 5,
            'color_name' => 'Black',
            'color_hex' => '#000000',
            'size' => '24 Inch',
            'is_default' => true,
            'is_active' => true,
        ]);

        $variantBlackSmall->attributeValues()->createMany([
            ['attribute_id' => $color->id, 'attribute_value_id' => $black->id],
            ['attribute_id' => $size->id, 'attribute_value_id' => $small->id],
        ]);

        $variantWhiteLarge = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'P-WHT-36',
            'variant_name' => 'White / 36 Inch',
            'price' => 1200,
            'stock_quantity' => 0,
            'color_name' => 'White',
            'color_hex' => '#FFFFFF',
            'size' => '36 Inch',
            'is_active' => true,
        ]);

        $variantWhiteLarge->attributeValues()->createMany([
            ['attribute_id' => $color->id, 'attribute_value_id' => $white->id],
            ['attribute_id' => $size->id, 'attribute_value_id' => $large->id],
        ]);

        $product->load([
            'variants.images',
            'variants.attributeValues.attribute',
            'variants.attributeValues.attributeValue',
        ]);

        $selector = ProductVariantSelector::forProduct($product);

        $this->assertTrue($selector['useAxisSelector']);
        $this->assertCount(2, $selector['axes']);
        $this->assertSame('color', $selector['axes'][0]['slug']);
        $this->assertSame('color', $selector['axes'][0]['type']);
        $this->assertSame('size', $selector['axes'][1]['slug']);

        $colorOptions = collect($selector['axes'][0]['options']);
        $this->assertTrue($colorOptions->contains(fn (array $option) => $option['value'] === 'Black' && $option['hex'] === '#000000'));
        $this->assertTrue($colorOptions->contains(fn (array $option) => $option['value'] === 'White' && $option['hex'] === '#FFFFFF'));

        $blackSmall = collect($selector['variants'])->firstWhere('sku', 'P-BLK-24');
        $this->assertSame(['color' => 'Black', 'size' => '24 Inch'], $blackSmall['options']);
        $this->assertTrue($blackSmall['purchasable']);

        $whiteLarge = collect($selector['variants'])->firstWhere('sku', 'P-WHT-36');
        $this->assertFalse($whiteLarge['purchasable']);
    }

    public function test_gallery_config_uses_variant_images_with_product_fallback(): void
    {
        $product = Product::factory()->create(['product_type' => 'variable']);

        $product->images()->create([
            'image_path' => 'products/shared.jpg',
            'alt_text' => 'Shared image',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-IMG',
            'variant_name' => 'Black',
            'price' => 5000,
            'stock_quantity' => 3,
            'is_default' => true,
            'is_active' => true,
        ]);

        $variant->images()->create([
            'product_id' => $product->id,
            'image_path' => 'products/variant-black.jpg',
            'alt_text' => 'Black variant',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $product->unsetRelation('variants');
        $product->load(['images', 'variants.images']);

        $selector = ProductVariantSelector::forProduct($product);

        $variantRow = collect($selector['variants'])->firstWhere('id', $variant->id);

        $this->assertStringContainsString('variant-black.jpg', $selector['gallery']['imagesByVariant'][$variant->id][0]['url']);
        $this->assertSame($selector['gallery']['imagesByVariant'][$variant->id], $variantRow['images']);
        $this->assertStringContainsString('shared.jpg', $selector['gallery']['fallbackImages'][0]['url']);
    }
}
