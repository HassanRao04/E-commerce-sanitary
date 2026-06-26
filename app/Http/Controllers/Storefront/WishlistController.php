<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToWishlistRequest;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(): View
    {
        $items = $this->wishlistService->items();

        return view('storefront.wishlist.index', compact('items'));
    }

    public function store(AddToWishlistRequest $request): RedirectResponse|JsonResponse
    {
        $this->wishlistService->add(
            (int) $request->validated('product_id'),
            $request->validated('product_variant_id'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'in_wishlist' => true,
                'count' => $this->wishlistService->itemCount(),
            ]);
        }

        return back()->with('success', 'Added to your wishlist.');
    }

    public function destroy(int $productId): RedirectResponse|JsonResponse
    {
        $this->wishlistService->remove($productId);

        if (request()->wantsJson()) {
            return response()->json([
                'in_wishlist' => false,
                'count' => $this->wishlistService->itemCount(),
            ]);
        }

        return back()->with('success', 'Removed from wishlist.');
    }
}
