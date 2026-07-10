<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\StorefrontContentService;
use App\Services\WishlistService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly StorefrontContentService $content,
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(): View
    {
        $homepage = $this->content->homepage();

        return view('storefront.home', [
            'sections' => $homepage['sections'],
            'carouselProducts' => $homepage['carouselProducts'],
            'categories' => $homepage['categories'],
            'brands' => $homepage['brands'],
            'featuredReviews' => $homepage['featuredReviews'],
            'trustSection' => $homepage['trust'],
            'wishlistProductIds' => $this->wishlistService->items()->pluck('product_id')->all(),
        ]);
    }
}
