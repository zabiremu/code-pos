<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the fresh-install path: this seeder is what a
 * buyer's first `php artisan migrate --seed` (or the /install wizard)
 * runs, and it depends on the spatie/laravel-permission tables existing
 * (see database/migrations/2025_01_01_000000_create_permission_tables.php).
 */
class RolesAndAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_every_pos_role(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        foreach (Role::values() as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_seeding_creates_a_demo_admin_that_can_log_in_and_is_an_admin(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole(Role::Admin->value));
    }

    public function test_seeding_twice_does_not_duplicate_roles_or_the_demo_admin(): void
    {
        $this->seed(RolesAndAdminSeeder::class);
        $this->seed(RolesAndAdminSeeder::class);

        $this->assertSame(count(Role::values()), \Spatie\Permission\Models\Role::count());
        $this->assertSame(1, User::where('email', 'admin@example.com')->count());
    }
}
