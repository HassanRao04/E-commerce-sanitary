<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Suspended => 'Suspended',
        };
    }

    public function isLoginAllowed(): bool
    {
        return $this !== self::Suspended;
    }

    public function canAccessAdmin(): bool
    {
        return $this === self::Active;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            self::Inactive => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            self::Suspended => 'bg-red-100 text-red-800 ring-red-600/20',
        };
    }
}
