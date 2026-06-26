<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait NormalizesStrings
{
    protected static function normalizeTrim(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }

    protected static function normalizeUpper(?string $value): ?string
    {
        return filled($value) ? Str::upper(trim($value)) : null;
    }

    protected static function normalizeLower(?string $value): ?string
    {
        return filled($value) ? Str::lower(trim($value)) : null;
    }
}
