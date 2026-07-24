<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductPipeLengthOption;
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
            'items.product.offers',
            'items.product.pipeLengthOptions',
            'items.productVariant',
            'items.productOffer',
            'items.pipeLengthOption',
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

            $item->loadMissing('pipeLengthOption');
            $displayPrice = $this->resolveUnitPrice(
                $item->productVariant,
                $item->pipeLengthOption,
                $customerType,
            );

            if ((float) $item->unit_price !== $displayPrice) {
                $item->update(['unit_price' => $displayPrice]);
                $item->unit_price = $displayPrice;
            }
        }

        return $cart;
    }

    public function addItem(
        int $productId,
        ?int $variantId,
        int $quantity,
        ?int $productOfferId = null,
        ?int $pipeLengthOptionId = null,
    ): CartItem {
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

        $offer = $this->resolveOffer($product, $productOfferId);
        $pipe = $this->resolvePipeLength($product, $pipeLengthOptionId);

        if ($offer) {
            $quantity = max(1, (int) $offer->buy_quantity);
        }

        $cart = $this->resolve();
        $existingQuery = $cart->items()->where('product_variant_id', $variant->id);

        if ($pipe) {
            $existingQuery->where('pipe_length_option_id', $pipe->id);
        } else {
            $existingQuery->whereNull('pipe_length_option_id');
        }

        $existing = $existingQuery->first();

        $heldInCart = $existing?->quantity ?? 0;
        $newTotal = $heldInCart + $quantity;
        $available = $this->inventory->availableQuantity($variant, $heldInCart);

        if ($newTotal > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} units available for {$product->name}.",
            ]);
        }

        $unitPrice = $this->resolveUnitPrice($variant, $pipe);
        $variantOptions = $this->buildVariantOptions($variant, $pipe, $offer, $product);

        if ($existing) {
            $this->inventory->reserve(
                $variant,
                $quantity,
                Cart::class,
                $cart->id,
                'Cart quantity increased',
            );

            $existing->update([
                'quantity' => $newTotal,
                'unit_price' => $unitPrice,
                'product_offer_id' => $offer?->id,
                'pipe_length_option_id' => $pipe?->id,
                'variant_options' => $variantOptions,
            ]);

            return $existing->fresh(['product', 'productVariant', 'productOffer', 'pipeLengthOption']);
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
            'product_offer_id' => $offer?->id,
            'pipe_length_option_id' => $pipe?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'variant_options' => $variantOptions,
        ])->load(['product', 'productVariant', 'productOffer', 'pipeLengthOption']);
    }

    /**
     * @param  array{product_offer_id?: int|null, pipe_length_option_id?: int|null}  $options
     */
    public function updateItem(CartItem $item, int $quantity, array $options = []): CartItem
    {
        $this->assertCartItemOwnership($item);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        $item->loadMissing(['product', 'productVariant', 'productOffer', 'pipeLengthOption']);
        $product = $item->product;
        $variant = $item->productVariant;

        $offerChanging = array_key_exists('product_offer_id', $options);
        $offer = $offerChanging
            ? $this->resolveOffer($product, $options['product_offer_id'])
            : $item->productOffer;

        $pipe = array_key_exists('pipe_length_option_id', $options)
            ? $this->resolvePipeLength($product, $options['pipe_length_option_id'])
            : $item->pipeLengthOption;

        if ($offerChanging) {
            // Offer is the source of truth: Buy 1 clears to qty 1; Buy N sets qty to N.
            $quantity = $offer ? max(1, (int) $offer->buy_quantity) : 1;
        } elseif ($offer && $quantity < $offer->buy_quantity) {
            $quantity = $offer->buy_quantity;
        }

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

        $item->update([
            'quantity' => $quantity,
            'product_offer_id' => $offer?->id,
            'pipe_length_option_id' => $pipe?->id,
            'unit_price' => $this->resolveUnitPrice($variant, $pipe),
            'variant_options' => $this->buildVariantOptions($variant, $pipe, $offer, $product),
        ]);

        return $item->fresh(['product', 'productVariant', 'productOffer', 'pipeLengthOption']);
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
                    'unit_price' => $this->resolveUnitPrice($variant, $item->pipeLengthOption),
                    'product_offer_id' => $item->product_offer_id ?? $existing->product_offer_id,
                    'pipe_length_option_id' => $item->pipe_length_option_id ?? $existing->pipe_length_option_id,
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

    /** @return array{subtotal: float, discount: float, shipping: float, service_charge: float, handling_charge: float, tax: float, grand_total: float, qualifies_for_free_shipping?: bool} */
    public function totals(?Cart $cart = null): array
    {
        $cart ??= $this->getCart();

        return collect($this->pricing->calculate($cart))
            ->only([
                'subtotal',
                'discount',
                'shipping',
                'service_charge',
                'handling_charge',
                'tax',
                'grand_total',
                'qualifies_for_free_shipping',
            ])
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
                    'product_offer_id' => $item->product_offer_id,
                    'pipe_length_option_id' => $item->pipe_length_option_id,
                    'image' => $item->product->primary_image_url,
                    'url' => route('shop.products.show', $item->product),
                ];
            })->values()->all(),
            'totals' => [
                ...$totals,
                'subtotal_formatted' => $symbol.' '.number_format($totals['subtotal'], 2),
                'discount_formatted' => $symbol.' '.number_format($totals['discount'], 2),
                'shipping_formatted' => $symbol.' '.number_format($totals['shipping'], 2),
                'service_charge_formatted' => $symbol.' '.number_format($totals['service_charge'], 2),
                'handling_charge_formatted' => $symbol.' '.number_format($totals['handling_charge'], 2),
                'tax_formatted' => $symbol.' '.number_format($totals['tax'], 2),
                'grand_total_formatted' => $symbol.' '.number_format($totals['grand_total'], 2),
                'qualifies_for_free_shipping' => (bool) ($totals['qualifies_for_free_shipping'] ?? false),
            ],
        ];
    }

    private function resolveUnitPrice(
        ProductVariant $variant,
        ?ProductPipeLengthOption $pipe = null,
        $customerType = null,
    ): float {
        $base = $this->productPricing->displayPrice($variant, $customerType);
        $addon = $pipe ? (float) $pipe->additional_price : 0.0;

        return round($base + $addon, 2);
    }

    private function resolveOffer(Product $product, ?int $offerId): ?ProductOffer
    {
        if (! $offerId) {
            return null;
        }

        if (! $product->offers_enabled) {
            throw ValidationException::withMessages([
                'product_offer_id' => 'Offers are not available for this product.',
            ]);
        }

        $offer = $product->offers()->whereKey($offerId)->first();

        if (! $offer) {
            throw ValidationException::withMessages([
                'product_offer_id' => 'The selected offer is not valid for this product.',
            ]);
        }

        return $offer;
    }

    private function resolvePipeLength(Product $product, ?int $pipeLengthOptionId): ?ProductPipeLengthOption
    {
        if (! $pipeLengthOptionId) {
            return null;
        }

        if (! $product->pipe_length_enabled) {
            throw ValidationException::withMessages([
                'pipe_length_option_id' => $product->resolvedOptionTitle().' options are not available for this product.',
            ]);
        }

        $pipe = $product->pipeLengthOptions()->whereKey($pipeLengthOptionId)->first();

        if (! $pipe) {
            throw ValidationException::withMessages([
                'pipe_length_option_id' => 'The selected '.$product->resolvedOptionTitle().' is not valid for this product.',
            ]);
        }

        return $pipe;
    }

    /**
     * @return list<array{name: string, slug: string, value: string}>
     */
    private function buildVariantOptions(
        ProductVariant $variant,
        ?ProductPipeLengthOption $pipe = null,
        ?ProductOffer $offer = null,
        ?Product $product = null,
    ): array {
        $options = VariantOptionFormatter::forVariant($variant);

        if ($pipe) {
            $product ??= $pipe->product ?? $variant->product;
            $optionTitle = $product?->resolvedOptionTitle() ?? 'Options';

            $options[] = [
                'name' => $optionTitle,
                'slug' => 'product_option',
                'value' => $pipe->label,
            ];
        }

        if ($offer) {
            $parts = ['Buy '.$offer->buy_quantity];
            $percent = (float) $offer->discount_percent;

            if ($percent > 0) {
                $parts[] = rtrim(rtrim(number_format($percent, 2), '0'), '.').'% OFF';
            }

            if ($offer->free_shipping) {
                $parts[] = 'Free Shipping';
            }

            $options[] = [
                'name' => 'Offer',
                'slug' => 'product_offer',
                'value' => implode(' · ', $parts),
            ];
        }

        return $options;
    }

    private function resolveVariant(Product $product, ?int $variantId): ProductVariant
    {
        if ($variantId) {
            return $product->variants()->active()->findOrFail($variantId);
        }

        $variant = $product->defaultVariant ?? $product->variants()->active()->orderBy('sort_order')->first();

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_id' => 'This product is not available for purchase.',
            ]);
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
