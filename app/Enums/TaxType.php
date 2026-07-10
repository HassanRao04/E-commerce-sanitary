<?php

namespace App\Enums;

enum TaxType: string
{
    case Vat = 'vat';
    case Gst = 'gst';
    case SalesTax = 'sales_tax';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Vat => 'VAT',
            self::Gst => 'GST',
            self::SalesTax => 'Sales Tax',
            self::None => 'No Tax',
        };
    }
}
