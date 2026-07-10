<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductVariantService $variantService,
        private readonly ProductVariationAttributeService $variationAttributeService,
        private readonly ProductImageService $imageService,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->products->search(
            $filters['q'] ?? null,
            $filters,
            $perPage
        );
    }

    public function findForEdit(int $id): Product
    {
        return Product::query()
            ->with([
                'brand',
                'categories',
                'defaultVariant',
                'variants' => fn ($query) => $query->orderBy('sort_order'),
                'variants.attributeValues.attribute',
                'variants.attributeValues.attributeValue',
                'variants.images',
                'images',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            [$productData, $relations] = $this->splitPayload($data);
            $product = $this->products->create($productData);
            $this->applyRelations($product, $relations);
            $this->activityLog->log('product.created', $product, null, $product->toArray());

            return $product->fresh([
                'brand', 'defaultVariant', 'categories', 'variants', 'images', 'attributeValues',
            ]);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $old = $product->toArray();
            [$productData, $relations] = $this->splitPayload($data);
            $product = $this->products->update($product, $productData);
            $this->applyRelations($product, $relations);
            $refreshed = $product->fresh([
                'brand', 'defaultVariant', 'categories', 'variants', 'images', 'attributeValues',
            ]);
            $this->activityLog->log('product.updated', $product, $old, $refreshed->toArray());

            return $refreshed;
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                $this->imageService->delete($image);
            }

            $this->activityLog->log('product.deleted', $product, $product->toArray());
            $this->products->delete($product);
        });
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function splitPayload(array $data): array
    {
        $relations = [
            'category_ids' => $data['category_ids'] ?? [],
            'product_attributes' => $data['product_attributes'] ?? [],
            'variants' => $data['variants'] ?? [],
            'images' => $data['images'] ?? [],
            'remove_image_ids' => $data['remove_image_ids'] ?? [],
            'primary_image_id' => $data['primary_image_id'] ?? null,
            'simple_pricing' => [
                'price' => $data['price'] ?? null,
                'sale_price' => $data['sale_price'] ?? null,
                'wholesale_price' => $data['wholesale_price'] ?? null,
                'dealer_price' => $data['dealer_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? config('shop.low_stock_threshold', 5),
            ],
        ];

        unset(
            $data['category_ids'],
            $data['product_attributes'],
            $data['variants'],
            $data['images'],
            $data['remove_image_ids'],
            $data['primary_image_id'],
            $data['price'],
            $data['sale_price'],
            $data['wholesale_price'],
            $data['dealer_price'],
            $data['cost_price'],
            $data['stock_quantity'],
            $data['low_stock_threshold'],
        );

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['product_type'] = $data['product_type'] ?? 'simple';
        $data['is_featured'] = ! empty($data['is_featured']);
        $data['is_new_arrival'] = ! empty($data['is_new_arrival']);
        $data['is_best_seller'] = ! empty($data['is_best_seller']);
        $data['is_project_suitable'] = ! empty($data['is_project_suitable']);

        return [$data, $relations];
    }

    private function applyRelations(Product $product, array $relations): void
    {
        $product->categories()->sync($relations['category_ids']);
        $this->syncProductAttributes($product, $relations['product_attributes']);

        if ($product->product_type === 'variable') {
            $variants = $relations['variants'];
            $attributeMap = $this->variationAttributeService->resolveAttributeMap($variants);
            $variants = $this->variationAttributeService->normalizeVariantRows($variants, $attributeMap);
            $this->variantService->syncVariable($product, $variants);
        } else {
            $this->variantService->syncSimple($product, $relations['simple_pricing']);
        }

        $this->imageService->syncFromRequest($product, [
            'images' => $relations['images'],
            'remove_image_ids' => $relations['remove_image_ids'],
            'primary_image_id' => $relations['primary_image_id'],
        ]);
    }

    private function syncProductAttributes(Product $product, array $attributes): void
    {
        $product->attributeValues()->delete();

        foreach ($attributes as $row) {
            if (empty($row['attribute_id'])) {
                continue;
            }

            if (empty($row['attribute_value_id']) && empty($row['custom_value'])) {
                continue;
            }

            ProductAttributeValue::create([
                'product_id' => $product->id,
                'attribute_id' => $row['attribute_id'],
                'attribute_value_id' => $row['attribute_value_id'] ?? null,
                'custom_value' => $row['custom_value'] ?? null,
            ]);
        }
    }
}
