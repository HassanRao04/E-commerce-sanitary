<?php

namespace App\Support;

use Carbon\CarbonInterface;

class AdminDateTime
{
    public static function displayTimezone(): string
    {
        return (string) config('shop.display_timezone', 'Asia/Karachi');
    }

    public static function format(?CarbonInterface $date, string $format = 'M j, Y g:i A'): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->timezone(static::displayTimezone())->format($format);
    }
}
