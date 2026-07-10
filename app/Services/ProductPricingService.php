<?php

namespace App\Services;

use App\Enums\CustomerType;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProductPricingService
{
    /**
     * @return array{
     *     base_price: float,
     *     sale_price: float|null,
     *     wholesale_price: float|null,
     *     dealer_price: float|null,
     *     display_price: float,
     *     compare_price: float|null,
     *     on_sale: bool,
     *     price_type: string
     * }
     */
    public function forVariant(ProductVariant $variant, ?CustomerType $customerType = null): array
    {
        $customerType ??= $this->customerType();

        $basePrice = (float) $variant->price;
        $salePrice = filled($variant->sale_price) ? (float) $variant->sale_price : null;
        $wholesalePrice = filled($variant->wholesale_price) ? (float) $variant->wholesale_price : null;
        $dealerPrice = filled($variant->dealer_price) ? (float) $variant->dealer_price : null;

        $displayPrice = match ($customerType) {
            CustomerType::Dealer => $dealerPrice ?? $wholesalePrice ?? $this->retailDisplayPrice($basePrice, $salePrice),
            CustomerType::Wholesale => $wholesalePrice ?? $this->retailDisplayPrice($basePrice, $salePrice),
            CustomerType::Retail => $this->retailDisplayPrice($basePrice, $salePrice),
        };

        $priceType = match ($customerType) {
            CustomerType::Dealer => $dealerPrice !== null ? 'dealer' : ($wholesalePrice !== null ? 'wholesale' : ($this->isRetailSale($basePrice, $salePrice) ? 'sale' : 'retail')),
            CustomerType::Wholesale => $wholesalePrice !== null ? 'wholesale' : ($this->isRetailSale($basePrice, $salePrice) ? 'sale' : 'retail'),
            CustomerType::Retail => $this->isRetailSale($basePrice, $salePrice) ? 'sale' : 'retail',
        };

        $comparePrice = $displayPrice < $basePrice ? $basePrice : null;

        return [
            'base_price' => $basePrice,
            'sale_price' => $salePrice,
            'wholesale_price' => $wholesalePrice,
            'dealer_price' => $dealerPrice,
            'display_price' => round($displayPrice, 2),
            'compare_price' => $comparePrice !== null ? round($comparePrice, 2) : null,
            'on_sale' => $comparePrice !== null,
            'price_type' => $priceType,
        ];
    }

    public function displayPrice(ProductVariant $variant, ?CustomerType $customerType = null): float
    {
        return $this->forVariant($variant, $customerType)['display_price'];
    }

    public function customerType(?User $user = null): CustomerType
    {
        $user ??= Auth::user();

        if (! $user) {
            return CustomerType::Retail;
        }

        $user->loadMissing('customer');

        return $user->customer?->customer_type ?? CustomerType::Retail;
    }

    public function format(float $amount): string
    {
        return config('shop.currency_symbol').' '.number_format($amount, 2);
    }

    /**
     * @return array{
     *     price: float,
     *     salePrice: float|null,
     *     wholesalePrice: float|null,
     *     dealerPrice: float|null,
     *     effectivePrice: float,
     *     priceFormatted: string,
     *     comparePriceFormatted: string|null,
     *     priceType: string
     * }
     */
    public function selectorPayload(ProductVariant $variant, ?CustomerType $customerType = null): array
    {
        $quote = $this->forVariant($variant, $customerType);

        return [
            'price' => $quote['base_price'],
            'salePrice' => $quote['sale_price'],
            'wholesalePrice' => $quote['wholesale_price'],
            'dealerPrice' => $quote['dealer_price'],
            'effectivePrice' => $quote['display_price'],
            'priceFormatted' => $this->format($quote['display_price']),
            'comparePriceFormatted' => $quote['compare_price'] !== null
                ? $this->format($quote['compare_price'])
                : null,
            'priceType' => $quote['price_type'],
        ];
    }

    private function retailDisplayPrice(float $basePrice, ?float $salePrice): float
    {
        if ($this->isRetailSale($basePrice, $salePrice)) {
            return (float) $salePrice;
        }

        return $basePrice;
    }

    private function isRetailSale(float $basePrice, ?float $salePrice): bool
    {
        return $salePrice !== null && $salePrice < $basePrice;
    }
}
