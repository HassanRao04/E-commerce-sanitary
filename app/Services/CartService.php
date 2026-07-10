<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\VariantOptionFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly CheckoutPricingService $pricing,
        private readonly InventoryControlService $inventory,
        private readonly ProductPricingService $productPricing,
    ) {}

    public function resolve(): Cart
    {
        $sessionKey = 'shop.cart_id';

        if ($cartId = session($sessionKey)) {
            $cart = Cart::query()->find($cartId);

            if ($cart) {
                if (Auth::check()) {
                    if ($cart->user_id && $cart->user_id !== Auth::id()) {
                        session()->forget($sessionKey);
                    } elseif ($cart->user_id === null) {
                        $cart->update([
                            'user_id' => Auth::id(),
                            'session_id' => null,
                        ]);

                        return $cart;
                    } else {
                        return $cart;
                    }
                } elseif ($cart->user_id === null) {
                    if ($cart->session_id !== session()->getId()) {
                        $cart->update(['session_id' => session()->getId()]);
                    }

                    return $cart;
                } else {
                    session()->forget($sessionKey);
                }
            } else {
                session()->forget($sessionKey);
            }
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
        return $this->syncCartPrices($this->resolve()->load([
            'items.product.brand',
            'items.product.images',
            'items.productVariant',
            'coupon',
        ]));
    }

    public function syncCartPrices(Cart $cart): Cart
    {
        $customerType = $this->productPricing->customerType();

        foreach ($cart->items as $item) {
            if (! $item->productVariant) {
                continue;
            }

            $displayPrice = $this->productPricing->displayPrice($item->productVariant, $customerType);

            if ((float) $item->unit_price !== $displayPrice) {
                $item->update(['unit_price' => $displayPrice]);
                $item->unit_price = $displayPrice;
            }
        }

        return $cart;
    }

    public function addItem(int $productId, ?int $variantId, int $quantity): CartItem
    {
        $product = Product::query()->active()->findOrFail($productId);

        if ($product->product_type === 'variable' && ! $variantId) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Please select all required options before adding to cart.',
            ]);
        }

        $variant = $this->resolveVariant($product, $variantId);

        if (! $variant->is_active) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The selected variation is not available.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        $cart = $this->resolve();
        $existing = $cart->items()
            ->where('product_variant_id', $variant->id)
            ->first();

        $heldInCart = $existing?->quantity ?? 0;
        $newTotal = $heldInCart + $quantity;
        $available = $this->inventory->availableQuantity($variant, $heldInCart);

        if ($newTotal > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} units available for {$product->name}.",
            ]);
        }

        if ($existing) {
            $this->inventory->reserve(
                $variant,
                $quantity,
                Cart::class,
                $cart->id,
                'Cart quantity increased',
            );

            $displayPrice = $this->productPricing->displayPrice($variant);

            $existing->update([
                'quantity' => $newTotal,
                'unit_price' => $displayPrice,
                'variant_options' => VariantOptionFormatter::forVariant($variant),
            ]);

            return $existing->fresh(['product', 'productVariant']);
        }

        $this->inventory->reserve(
            $variant,
            $quantity,
            Cart::class,
            $cart->id,
            'Added to cart',
        );

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price' => $this->productPricing->displayPrice($variant),
            'variant_options' => VariantOptionFormatter::forVariant($variant),
        ])->load(['product', 'productVariant']);
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        $this->assertCartItemOwnership($item);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        $variant = $item->productVariant;
        $previousQuantity = $item->quantity;

        if ($quantity > $previousQuantity) {
            $delta = $quantity - $previousQuantity;
            $available = $this->inventory->availableQuantity($variant, $previousQuantity);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$available} units available.",
                ]);
            }

            $this->inventory->reserve(
                $variant,
                $delta,
                CartItem::class,
                $item->id,
                'Cart line quantity increased',
            );
        } elseif ($quantity < $previousQuantity) {
            $this->inventory->release(
                $variant,
                $previousQuantity - $quantity,
                CartItem::class,
                $item->id,
                'Cart line quantity decreased',
            );
        }

        $item->update(['quantity' => $quantity]);

        return $item->fresh(['product', 'productVariant']);
    }

    public function removeItem(CartItem $item): void
    {
        $this->assertCartItemOwnership($item);

        $variant = $item->productVariant;

        if ($variant && $item->quantity > 0) {
            $this->inventory->release(
                $variant,
                $item->quantity,
                CartItem::class,
                $item->id,
                'Removed from cart',
            );
        }

        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->load('items.productVariant');

        foreach ($cart->items as $item) {
            if ($item->productVariant && $item->quantity > 0) {
                $this->inventory->release(
                    $item->productVariant,
                    $item->quantity,
                    CartItem::class,
                    $item->id,
                    'Cart cleared',
                );
            }
        }

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

        $existingByVariant = $userCart->items->keyBy('product_variant_id');

        foreach ($guestCart->items as $item) {
            $variant = $item->productVariant;

            if (! $variant) {
                $item->delete();

                continue;
            }

            $existing = $existingByVariant->get($item->product_variant_id);

            if ($existing) {
                $desiredQuantity = $existing->quantity + $item->quantity;

                $this->inventory->release(
                    $variant,
                    $item->quantity,
                    CartItem::class,
                    $item->id,
                    'Guest cart merged',
                );

                $maxAllowed = $this->inventory->availableQuantity($variant, $existing->quantity);
                $newQuantity = min($desiredQuantity, $maxAllowed);
                $delta = $newQuantity - $existing->quantity;

                if ($delta > 0) {
                    $this->inventory->reserve(
                        $variant,
                        $delta,
                        Cart::class,
                        $userCart->id,
                        'Guest cart merged',
                    );
                }

                $existing->update([
                    'quantity' => $newQuantity,
                    'unit_price' => $this->productPricing->displayPrice($variant),
                    'variant_options' => $item->variant_options
                        ?? VariantOptionFormatter::forVariant($variant),
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

    /** @return array{subtotal: float, discount: float, shipping: float, service_charge: float, handling_charge: float, tax: float, grand_total: float} */
    public function totals(?Cart $cart = null): array
    {
        $cart ??= $this->getCart();

        return collect($this->pricing->calculate($cart))
            ->only(['subtotal', 'discount', 'shipping', 'service_charge', 'handling_charge', 'tax', 'grand_total'])
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
                $variantOptions = $item->variant_options
                    ?? ($item->productVariant ? VariantOptionFormatter::forVariant($item->productVariant) : []);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'variant' => VariantOptionFormatter::labelOrFallback(
                        $variantOptions,
                        $item->productVariant?->variant_name,
                    ),
                    'variant_options' => $variantOptions,
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
