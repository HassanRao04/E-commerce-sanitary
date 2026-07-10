<?php

namespace App\Enums;

enum ShippingMethod: string
{
    case Flat = 'flat';
    case Product = 'product';
    case Category = 'category';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Flat rate',
            self::Product => 'Product-based',
            self::Category => 'Category-based',
        };
    }
}
