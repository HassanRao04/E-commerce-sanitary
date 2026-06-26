{{-- Deprecated: use <x-storefront.header /> in layouts/storefront.blade.php --}}
<x-storefront.header
    :cart-item-count="$cartItemCount ?? 0"
    :wishlist-item-count="$wishlistItemCount ?? 0"
    :header-cart-preview="$headerCartPreview ?? ['count' => 0, 'items' => [], 'totals' => []]"
    :header-nav-categories="$headerNavCategories ?? collect()"
    :header-nav-brands="$headerNavBrands ?? collect()"
/>
