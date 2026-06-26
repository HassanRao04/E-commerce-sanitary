<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(4, true);

        return [
            'brand_id' => Brand::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'base_sku' => Str::upper(fake()->unique()->bothify('PRD-####')),
            'product_type' => 'simple',
            'status' => ProductStatus::Active,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'is_featured' => false,
            'is_new_arrival' => false,
            'is_best_seller' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if ($product->variants()->exists()) {
                return;
            }

            $variant = ProductVariant::factory()->for($product)->create([
                'is_default' => true,
            ]);

            $product->update(['default_variant_id' => $variant->id]);
        });
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Draft]);
    }
}
