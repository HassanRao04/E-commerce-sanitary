<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Pending = 'pending';
    case Replied = 'replied';
    case Closed = 'closed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Pending => 'Pending',
            self::Replied => 'Replied',
            self::Closed => 'Closed',
            self::Spam => 'Spam',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Pending], true);
    }

    /** @return list<self> */
    public static function filterable(): array
    {
        return [
            self::New,
            self::Pending,
            self::Replied,
            self::Closed,
            self::Spam,
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
