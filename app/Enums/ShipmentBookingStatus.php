<?php

namespace App\Enums;

enum ShipmentBookingStatus: string
{
    case Manual = 'manual';
    case Draft = 'draft';
    case Booked = 'booked';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isApiManaged(): bool
    {
        return in_array($this, [self::Draft, self::Booked], true);
    }
}
