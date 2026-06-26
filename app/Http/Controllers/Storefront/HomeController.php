<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Review;
use App\Services\Storefront\HomeCatalogService;
use App\Services\WishlistService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeCatalogService $catalog,
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(): View
    {
        $featuredProducts = $this->catalog->featured();
        $bestSellingProducts = $this->catalog->bestSelling();
        $newArrivals = $this->catalog->newArrivals();
        $trendingProducts = $this->catalog->trending();
        $flashSaleProducts = $this->catalog->flashSale();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->take(8)
            ->get();

        $wishlistProductIds = $this->wishlistService->items()
            ->pluck('product_id')
            ->all();

        $featuredReviews = Review::query()
            ->approved()
            ->recent()
            ->whereNotNull('body')
            ->with(['user:id,name', 'product:id,name'])
            ->take(6)
            ->get();

        return view('storefront.home', compact(
            'featuredProducts',
            'bestSellingProducts',
            'newArrivals',
            'trendingProducts',
            'flashSaleProducts',
            'categories',
            'brands',
            'wishlistProductIds',
            'featuredReviews',
        ));
    }
}
