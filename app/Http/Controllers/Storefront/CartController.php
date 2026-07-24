<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CheckoutPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutPricingService $pricing,
    ) {}

    public function index(): View
    {
        $cart = $this->cartService->getCart();
        $pricing = $this->pricing->calculate($cart);
        $totals = collect($pricing)->only(['subtotal', 'discount', 'shipping', 'service_charge', 'handling_charge', 'tax', 'grand_total'])->all();

        return view('storefront.cart.index', compact('cart', 'totals', 'pricing'));
    }

    public function preview(): JsonResponse
    {
        return response()->json($this->cartService->preview());
    }

    public function store(AddToCartRequest $request): RedirectResponse|JsonResponse
    {
        $this->cartService->addItem(
            $request->integer('product_id'),
            $request->integer('product_variant_id') ?: null,
            $request->integer('quantity'),
            $request->integer('product_offer_id') ?: null,
            $request->integer('pipe_length_option_id') ?: null,
        );

        if ($request->wantsJson()) {
            return response()->json($this->cartService->preview());
        }

        if ($request->boolean('buy_now')) {
            return redirect()
                ->route('shop.checkout.index')
                ->with('success', 'Item added. Complete your purchase below.');
        }

        return redirect()
            ->route('shop.cart.index')
            ->with('success', 'Item added to cart.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $options = [];

        if ($request->exists('product_offer_id')) {
            $options['product_offer_id'] = $request->integer('product_offer_id') ?: null;
        }

        if ($request->exists('pipe_length_option_id')) {
            $options['pipe_length_option_id'] = $request->integer('pipe_length_option_id') ?: null;
        }

        $this->cartService->updateItem($cartItem, $request->integer('quantity'), $options);

        if ($request->wantsJson()) {
            return response()->json($this->cartService->preview());
        }

        return redirect()
            ->route('shop.cart.index')
            ->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $this->cartService->removeItem($cartItem);

        if (request()->wantsJson()) {
            return response()->json($this->cartService->preview());
        }

        return redirect()
            ->route('shop.cart.index')
            ->with('success', 'Item removed from cart.');
    }
}
