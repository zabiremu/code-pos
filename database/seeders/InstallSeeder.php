<?php

namespace Database\Seeders;

use App\Enums\Role as PosRole;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * What the web installer seeds on a buyer's real install: the POS roles and
 * the default branch. Deliberately NO users - the installer's admin step
 * creates the buyer's own admin, so no install ships a publicly known
 * login. Demo users and sample data live in DatabaseSeeder (dev / live demo).
 */
class InstallSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PosRole::values() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            ['tax_rate' => 0, 'currency' => 'USD', 'is_active' => true]
        );
    }
}
