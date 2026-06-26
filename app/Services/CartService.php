<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly CheckoutPricingService $pricing,
    ) {}

    public function resolve(): Cart
    {
        $sessionKey = 'shop.cart_id';

        if ($cartId = session($sessionKey)) {
            $cart = Cart::query()->find($cartId);

            if ($cart) {
                if (Auth::check() && $cart->user_id !== Auth::id()) {
                    $cart->update([
                        'user_id' => Auth::id(),
                        'session_id' => null,
                    ]);
                }

                return $cart;
            }

            session()->forget($sessionKey);
        }

        if (! Auth::check()) {
            $cart = Cart::query()->where('session_id', session()->getId())->first();

            if ($cart) {
                session([$sessionKey => $cart->id]);

                return $cart;
            }
        }

        if (Auth::check()) {
            $cart = Cart::query()->firstOrCreate(['user_id' => Auth::id()]);
        } else {
            $cart = Cart::query()->firstOrCreate([
                'session_id' => session()->getId(),
            ]);
        }

        session([$sessionKey => $cart->id]);

        return $cart;
    }

    public function getCart(): Cart
    {
        return $this->resolve()->load([
            'items.product.brand',
            'items.product.images',
            'items.productVariant',
            'coupon',
        ]);
    }

    public function addItem(int $productId, ?int $variantId, int $quantity): CartItem
    {
        $product = Product::query()->active()->findOrFail($productId);
        $variant = $this->resolveVariant($product, $variantId);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        if ($variant->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$variant->stock_quantity} units available for {$product->name}.",
            ]);
        }

        $cart = $this->resolve();
        $existing = $cart->items()
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($existing) {
            $newQuantity = $existing->quantity + $quantity;

            if ($newQuantity > $variant->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$variant->stock_quantity} units available in total.",
                ]);
            }

            $existing->update(['quantity' => $newQuantity]);

            return $existing->fresh(['product', 'productVariant']);
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price' => $variant->effective_price,
        ])->load(['product', 'productVariant']);
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        $this->assertCartItemOwnership($item);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        $variant = $item->productVariant;

        if ($quantity > $variant->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$variant->stock_quantity} units available.",
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh(['product', 'productVariant']);
    }

    public function removeItem(CartItem $item): void
    {
        $this->assertCartItemOwnership($item);
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);
        session()->forget('shop.cart_id');
    }

    public function mergeSessionCartIntoUser(int $userId): void
    {
        $sessionKey = 'shop.cart_id';
        $guestCart = null;

        if ($cartId = session($sessionKey)) {
            $guestCart = Cart::query()->with('items.productVariant')->find($cartId);
        }

        if (! $guestCart) {
            $guestCart = Cart::query()
                ->with('items.productVariant')
                ->where('session_id', session()->getId())
                ->whereNull('user_id')
                ->first();
        }

        if (! $guestCart || $guestCart->items->isEmpty()) {
            if (Auth::id() === $userId) {
                $this->resolve();
            }

            return;
        }

        if ($guestCart->user_id === $userId) {
            session([$sessionKey => $guestCart->id]);

            return;
        }

        $userCart = Cart::query()->firstOrCreate(['user_id' => $userId]);
        $userCart->load('items');

        foreach ($guestCart->items as $item) {
            $existing = $userCart->items()
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existing) {
                $maxStock = $item->productVariant->stock_quantity;
                $existing->update([
                    'quantity' => min($existing->quantity + $item->quantity, $maxStock),
                ]);
                $item->delete();
            } else {
                $item->update(['cart_id' => $userCart->id]);
            }
        }

        if ($guestCart->coupon_id && ! $userCart->coupon_id) {
            $userCart->update(['coupon_id' => $guestCart->coupon_id]);
        }

        if ($guestCart->id !== $userCart->id) {
            $guestCart->delete();
        }

        session([$sessionKey => $userCart->id]);
    }

    public function itemCount(?Cart $cart = null): int
    {
        $cart ??= $this->resolve();

        return (int) $cart->items()->sum('quantity');
    }

    /** @return array{subtotal: float, discount: float, shipping: float, tax: float, grand_total: float} */
    public function totals(?Cart $cart = null): array
    {
        $cart ??= $this->getCart();

        return collect($this->pricing->calculate($cart))
            ->only(['subtotal', 'discount', 'shipping', 'tax', 'grand_total'])
            ->all();
    }

    /** @return array<string, mixed> */
    public function preview(?Cart $cart = null): array
    {
        $cart ??= $this->getCart();
        $totals = $this->totals($cart);
        $symbol = config('shop.currency_symbol');

        return [
            'count' => $this->itemCount($cart),
            'items' => $cart->items->map(function (CartItem $item) use ($symbol): array {
                $lineTotal = (float) $item->unit_price * $item->quantity;

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'variant' => $item->productVariant?->variant_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => $lineTotal,
                    'unit_price_formatted' => $symbol.' '.number_format((float) $item->unit_price, 2),
                    'line_total_formatted' => $symbol.' '.number_format($lineTotal, 2),
                    'image' => $item->product->primary_image_url,
                    'url' => route('shop.products.show', $item->product),
                ];
            })->values()->all(),
            'totals' => [
                ...$totals,
                'subtotal_formatted' => $symbol.' '.number_format($totals['subtotal'], 2),
                'discount_formatted' => $symbol.' '.number_format($totals['discount'], 2),
                'shipping_formatted' => $symbol.' '.number_format($totals['shipping'], 2),
                'tax_formatted' => $symbol.' '.number_format($totals['tax'], 2),
                'grand_total_formatted' => $symbol.' '.number_format($totals['grand_total'], 2),
            ],
        ];
    }

    private function resolveVariant(Product $product, ?int $variantId): ProductVariant
    {
        if ($variantId) {
            return $product->variants()->active()->findOrFail($variantId);
        }

        $variant = $product->defaultVariant ?? $product->variants()->active()->orderBy('sort_order')->first();

        if (! $variant) {
            throw ValidationException::withMessages(['product' => 'This product is not available for purchase.']);
        }

        return $variant;
    }

    private function assertCartItemOwnership(CartItem $item): void
    {
        $cart = $this->resolve();

        if ($item->cart_id !== $cart->id) {
            abort(403);
        }
    }
}
