<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductOffer;

class ProductOfferCalculatorService
{
    public function lineDiscount(CartItem $item): float
    {
        $offer = $this->qualifyingOffer($item);

        if (! $offer) {
            return 0.0;
        }

        $percent = (float) $offer->discount_percent;

        if ($percent <= 0) {
            return 0.0;
        }

        $lineTotal = (float) $item->unit_price * $item->quantity;

        return round($lineTotal * ($percent / 100), 2);
    }

    public function cartOfferDiscount(Cart $cart): float
    {
        $cart->loadMissing(['items.product', 'items.productOffer']);

        return round($cart->items->sum(
            fn (CartItem $item): float => $this->lineDiscount($item)
        ), 2);
    }

    public function cartQualifiesForOfferFreeShipping(Cart $cart): bool
    {
        $cart->loadMissing(['items.product', 'items.productOffer']);

        foreach ($cart->items as $item) {
            $offer = $this->qualifyingOffer($item);

            if ($offer?->free_shipping) {
                return true;
            }
        }

        return false;
    }

    /**
     * Immutable snapshot of offer + pipe pricing for an order/invoice line.
     *
     * @return array{
     *     selected_offer: string|null,
     *     option_title: string|null,
     *     original_unit_price: float,
     *     discount_percent: float,
     *     discount_amount: float,
     *     pipe_length: string|null,
     *     pipe_extra_cost: float,
     *     unit_price: float,
     *     total: float
     * }
     */
    public function snapshotLine(CartItem $item): array
    {
        $item->loadMissing(['productOffer', 'pipeLengthOption', 'product']);

        $pipe = $item->pipeLengthOption;
        $pipeExtra = $pipe ? (float) $pipe->additional_price : 0.0;
        $unitPrice = round((float) $item->unit_price, 2);
        $originalUnitPrice = round(max(0, $unitPrice - $pipeExtra), 2);
        $offer = $this->qualifyingOffer($item);
        $discountAmount = $this->lineDiscount($item);

        return [
            'selected_offer' => $offer ? $this->formatOfferLabel($offer) : null,
            'option_title' => $pipe ? ($item->product?->resolvedOptionTitle() ?? 'Options') : null,
            'original_unit_price' => $originalUnitPrice,
            'discount_percent' => $offer ? (float) $offer->discount_percent : 0.0,
            'discount_amount' => $discountAmount,
            'pipe_length' => $pipe?->label,
            'pipe_extra_cost' => $pipeExtra,
            'unit_price' => $unitPrice,
            'total' => round($unitPrice * $item->quantity, 2),
        ];
    }

    public function formatOfferLabel(ProductOffer $offer): string
    {
        $parts = ['Buy '.$offer->buy_quantity];
        $percent = (float) $offer->discount_percent;

        if ($percent > 0) {
            $parts[] = rtrim(rtrim(number_format($percent, 2), '0'), '.').'% OFF';
        }

        if ($offer->free_shipping) {
            $parts[] = 'Free Shipping';
        }

        return implode(' · ', $parts);
    }

    private function qualifyingOffer(CartItem $item): ?ProductOffer
    {
        $item->loadMissing(['product', 'productOffer']);

        $product = $item->product;
        $offer = $item->productOffer;

        if (! $product?->offers_enabled || ! $offer) {
            return null;
        }

        if ((int) $offer->product_id !== (int) $item->product_id) {
            return null;
        }

        if ($item->quantity < $offer->buy_quantity) {
            return null;
        }

        return $offer;
    }
}
