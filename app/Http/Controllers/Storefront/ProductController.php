<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ProductCatalogService;
use App\Services\ReviewSettingsService;
use App\Services\WishlistService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $catalog,
        private readonly WishlistService $wishlistService,
        private readonly ReviewSettingsService $reviewSettings,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        return $this->renderBrowse($request, $this->catalog->baseQuery(), [
            'pageTitle' => 'Shop',
            'breadcrumbLabel' => 'Shop',
            'showCategoryFilter' => true,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        $product->load([
            'brand',
            'categories',
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            'documents',
            'variants' => fn ($q) => $q->active()->orderBy('sort_order'),
            'variants.images',
            'variants.attributeValues.attribute',
            'variants.attributeValues.attributeValue',
            'attributeValues.attribute',
            'attributeValues.attributeValue',
        ]);

        $relatedProducts = Product::query()
            ->active()
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product): void {
                $q->where('brand_id', $product->brand_id)
                    ->orWhereHas('categories', fn ($cat) => $cat->whereIn(
                        'categories.id',
                        $product->categories->pluck('id')
                    ));
            })
            ->with(['brand', 'defaultVariant', 'images'])
            ->take(8)
            ->get();

        $reviews = collect();
        $reviewStats = ['average' => 0.0, 'count' => 0];

        if ($this->reviewSettings->reviewsEnabled()) {
            $reviews = Review::query()
                ->approved()
                ->forProduct($product->id)
                ->with(['user:id,name', 'images'])
                ->recent()
                ->take(20)
                ->get();

            $reviewAggregate = Review::query()
                ->approved()
                ->forProduct($product->id)
                ->selectRaw('ROUND(AVG(rating), 1) as average, COUNT(*) as count')
                ->first();

            $reviewStats = [
                'average' => (float) ($reviewAggregate->average ?? 0),
                'count' => (int) ($reviewAggregate->count ?? 0),
            ];
        }

        $wishlistProductIds = $this->wishlistService->items()->pluck('product_id')->all();
        $inWishlist = in_array($product->id, $wishlistProductIds, true);

        return view('storefront.products.show', compact(
            'product',
            'relatedProducts',
            'reviews',
            'reviewStats',
            'inWishlist',
            'wishlistProductIds',
        ));
    }

    /** @param  array<string, mixed>  $meta */
    private function renderBrowse(Request $request, Builder $baseQuery, array $meta): View|JsonResponse
    {
        $query = $this->catalog->applyFilters($baseQuery, $request);
        $products = $this->catalog->paginate($query, $request);
        ['brands' => $brands, 'categories' => $categories] = $this->catalog->filterOptions();
        $priceRange = $this->catalog->priceRange();
        $search = $request->string('q')->trim()->toString();
        $wishlistProductIds = $this->wishlistService->items()->pluck('product_id')->all();

        $payload = compact(
            'products',
            'brands',
            'categories',
            'priceRange',
            'search',
            'wishlistProductIds',
        ) + $meta;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('storefront.products.partials.results', $payload)->render(),
                'total' => $products->total(),
            ]);
        }

        return view('storefront.products.index', $payload);
    }
}
