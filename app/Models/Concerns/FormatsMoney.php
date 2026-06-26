<?php

namespace App\Models\Concerns;

trait FormatsMoney
{
    protected function formatMoneyAttribute(?string $amount): string
    {
        return config('shop.currency_symbol').' '.number_format((float) $amount, 2);
    }
}
