<?php

namespace App\Enums;

enum UserActivityAction: string
{
    case Created = 'user.created';
    case Updated = 'user.updated';
    case Deleted = 'user.deleted';
    case Login = 'user.login';
    case Logout = 'user.logout';
    case PasswordReset = 'user.password_reset';
    case RoleChanged = 'user.role_changed';
    case RoleAssigned = 'user.role_assigned';
    case RoleRemoved = 'user.role_removed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'User Created',
            self::Updated => 'User Updated',
            self::Deleted => 'User Deleted',
            self::Login => 'User Login',
            self::Logout => 'User Logout',
            self::PasswordReset => 'Password Reset',
            self::RoleChanged => 'Role Changed',
            self::RoleAssigned => 'Role Assigned',
            self::RoleRemoved => 'Role Removed',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
