<?php

namespace Database\Seeders;

use App\Enums\Role as PosRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A manager and a cashier for the live demo's one-click logins, so reviewers
 * can see what each role can and can't do. Dev / live demo only - the web
 * installer never runs this.
 */
class DemoStaffSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const ACCOUNTS = [
        PosRole::Manager->value => ['name' => 'Demo Manager', 'email' => 'manager@example.com'],
        PosRole::Cashier->value => ['name' => 'Demo Cashier', 'email' => 'cashier@example.com'],
    ];

    public function run(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Main Branch'], ['tax_rate' => 0, 'currency' => 'USD', 'is_active' => true]);

        foreach (self::ACCOUNTS as $role => $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'branch_id' => $branch->id, 'password' => self::PASSWORD, 'is_active' => true]
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
