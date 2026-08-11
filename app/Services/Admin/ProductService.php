<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
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
                'offers',
                'pipeLengthOptions',
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
                'brand', 'defaultVariant', 'categories', 'variants', 'images', 'attributeValues', 'offers', 'pipeLengthOptions',
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
                'brand', 'defaultVariant', 'categories', 'variants', 'images', 'attributeValues', 'offers', 'pipeLengthOptions',
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

    public function restore(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $snapshot = ActivityLog::query()
                ->where('model_type', Product::class)
                ->where('model_id', $product->id)
                ->where('action', 'product.deleted')
                ->latest('created_at')
                ->value('old_values') ?? $product->toArray();

            $product->restore();

            $restored = $product->fresh();
            $this->activityLog->log('product.restored', $restored, $snapshot, $restored->toArray());

            return $restored;
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
            'offer_tiers' => $data['offer_tiers'] ?? [],
            'pipe_length_options' => $data['pipe_length_options'] ?? [],
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
            $data['offer_tiers'],
            $data['pipe_length_options'],
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
        $data['offers_enabled'] = ! empty($data['offers_enabled']);
        $data['pipe_length_enabled'] = ! empty($data['pipe_length_enabled']);

        return [$data, $relations];
    }

    private function applyRelations(Product $product, array $relations): void
    {
        $product->categories()->sync($relations['category_ids']);
        $this->syncProductAttributes($product, $relations['product_attributes']);
        $this->syncOfferTiers($product, $relations['offer_tiers']);
        $this->syncPipeLengthOptions($product, $relations['pipe_length_options']);

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

    /**
     * @param  list<array{label?: mixed, additional_price?: mixed}>  $options
     */
    private function syncPipeLengthOptions(Product $product, array $options): void
    {
        $product->pipeLengthOptions()->delete();

        $sortOrder = 0;

        foreach ($options as $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $product->pipeLengthOptions()->create([
                'label' => $label,
                'additional_price' => max(0, (float) ($row['additional_price'] ?? 0)),
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    /**
     * @param  list<array{buy_quantity?: mixed, discount_percent?: mixed, free_shipping?: mixed}>  $tiers
     */
    private function syncOfferTiers(Product $product, array $tiers): void
    {
        $product->offers()->delete();

        $sortOrder = 0;
        $seenQuantities = [];

        foreach ($tiers as $row) {
            $buyQuantity = (int) ($row['buy_quantity'] ?? 0);

            if ($buyQuantity < 1 || isset($seenQuantities[$buyQuantity])) {
                continue;
            }

            $seenQuantities[$buyQuantity] = true;

            $product->offers()->create([
                'buy_quantity' => $buyQuantity,
                'discount_percent' => max(0, min(100, (float) ($row['discount_percent'] ?? 0))),
                'free_shipping' => ! empty($row['free_shipping']),
                'sort_order' => $sortOrder++,
            ]);
        }
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
