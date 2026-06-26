<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $sku = Str::upper(fake()->unique()->bothify('SKU-####-??'));

        return [
            'product_id' => Product::factory(),
            'sku' => $sku,
            'variant_name' => 'Default',
            'price' => fake()->randomFloat(2, 1000, 50000),
            'stock_quantity' => fake()->numberBetween(5, 100),
            'low_stock_threshold' => 5,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock_quantity' => 0]);
    }
}
