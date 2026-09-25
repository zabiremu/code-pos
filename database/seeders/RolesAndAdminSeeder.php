<?php

namespace Database\Seeders;

use App\Enums\Role as PosRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Seed the fixed POS roles and one demo admin user + default branch,
     * so a fresh install has something to log into immediately.
     */
    public function run(): void
    {
        foreach (PosRole::values() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $branch = Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            ['tax_rate' => 0, 'currency' => 'USD', 'is_active' => true]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'branch_id' => $branch->id,
                'password' => Hash::make('password'), // demo credential only — shown on the live preview, never used in a real deploy
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $admin->hasRole(PosRole::Admin->value)) {
            $admin->assignRole(PosRole::Admin->value);
        }
    }
}
