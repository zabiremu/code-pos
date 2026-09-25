<?php

namespace App\Enums;

/** The fixed set of POS roles, seeded by RolesAndAdminSeeder and checked by role middleware. */
enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Waiter = 'waiter';
    case Kitchen = 'kitchen';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Cashier => 'Cashier',
            self::Waiter => 'Waiter',
            self::Kitchen => 'Kitchen',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
