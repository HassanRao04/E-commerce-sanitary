<?php

namespace App\Enums;

enum StaffRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case InventoryStaff = 'inventory-staff';
    case SalesStaff = 'sales-staff';
    case ContentManager = 'content-manager';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::InventoryStaff => 'Inventory Staff',
            self::SalesStaff => 'Sales Staff',
            self::ContentManager => 'Content Manager',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SuperAdmin => 'bg-purple-100 text-purple-800 ring-purple-600/20',
            self::Admin => 'bg-indigo-100 text-indigo-800 ring-indigo-600/20',
            self::Manager => 'bg-blue-100 text-blue-800 ring-blue-600/20',
            self::InventoryStaff => 'bg-cyan-100 text-cyan-800 ring-cyan-600/20',
            self::SalesStaff => 'bg-teal-100 text-teal-800 ring-teal-600/20',
            self::ContentManager => 'bg-violet-100 text-violet-800 ring-violet-600/20',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromName(string $name): ?self
    {
        return self::tryFrom($name);
    }
}
