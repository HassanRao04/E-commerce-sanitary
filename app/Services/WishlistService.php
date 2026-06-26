<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    public function resolveQuery()
    {
        if (Auth::check()) {
            return Wishlist::query()->where('user_id', Auth::id());
        }

        return Wishlist::query()
            ->whereNull('user_id')
            ->where('session_id', session()->getId());
    }

    public function items(): Collection
    {
        return $this->resolveQuery()
            ->with(['product.brand', 'product.defaultVariant', 'product.images', 'productVariant'])
            ->latest('id')
            ->get();
    }

    public function itemCount(): int
    {
        return $this->resolveQuery()->count();
    }

    public function contains(int $productId): bool
    {
        return $this->resolveQuery()->where('product_id', $productId)->exists();
    }

    public function add(int $productId, ?int $variantId = null): Wishlist
    {
        $product = Product::query()->active()->findOrFail($productId);
        $variant = $this->resolveVariant($product, $variantId);

        $existing = $this->resolveQuery()
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            if ($variant && $existing->product_variant_id !== $variant->id) {
                $existing->update(['product_variant_id' => $variant->id]);
            }

            return $existing;
        }

        return Wishlist::query()->create([
            'user_id' => Auth::id(),
            'session_id' => Auth::check() ? null : session()->getId(),
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
    }

    public function remove(int $productId): void
    {
        $this->resolveQuery()
            ->where('product_id', $productId)
            ->delete();
    }

    public function mergeGuestWishlist(int $userId): void
    {
        $sessionId = session()->getId();

        Wishlist::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->each(function (Wishlist $item) use ($userId): void {
                $exists = Wishlist::query()
                    ->where('user_id', $userId)
                    ->where('product_id', $item->product_id)
                    ->exists();

                if ($exists) {
                    $item->delete();

                    return;
                }

                $item->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);
            });
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if ($variantId) {
            $variant = $product->variants()->active()->find($variantId);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => 'Selected variant is not available.',
                ]);
            }

            return $variant;
        }

        return $product->defaultVariant;
    }
}
