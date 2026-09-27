<?php

namespace Tests\Concerns;

use App\Enums\Role as PosRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

/** Shared helper for feature tests that need a logged-in staff member with a given POS role. */
trait CreatesStaff
{
    protected function staff(string $role, array $attributes = []): User
    {
        foreach (PosRole::values() as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
