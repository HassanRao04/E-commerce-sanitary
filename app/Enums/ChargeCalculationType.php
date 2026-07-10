<?php

namespace App\Enums;

enum ChargeCalculationType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }
}
