<?php

namespace App\Enums;

/** The fixed set of POS roles, seeded by RolesAndAdminSeeder and checked by role middleware. */
enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Cashier => 'Cashier',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
