<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ProductCatalogService;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $catalog,
        private readonly WishlistService $wishlistService,
    ) {}

    public function show(Category $category, Request $request): View|JsonResponse
    {
        abort_unless($category->is_active, 404);

        $query = $this->catalog->baseQuery()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id));

        $query = $this->catalog->applyFilters($query, $request);
        $products = $this->catalog->paginate($query, $request);
        ['brands' => $brands, 'categories' => $categories] = $this->catalog->filterOptions();
        $priceRange = $this->catalog->priceRange();
        $search = $request->string('q')->trim()->toString();
        $wishlistProductIds = $this->wishlistService->items()->pluck('product_id')->all();

        $payload = compact(
            'category',
            'products',
            'brands',
            'categories',
            'priceRange',
            'search',
            'wishlistProductIds',
        ) + [
            'pageTitle' => $category->name,
            'breadcrumbLabel' => $category->name,
            'showCategoryFilter' => false,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('storefront.products.partials.results', $payload)->render(),
                'total' => $products->total(),
            ]);
        }

        return view('storefront.categories.show', $payload);
    }
}
