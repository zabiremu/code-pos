<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * Regression coverage for the role-gated route groups in routes/web.php.
 * These once used 'role:admin,manager' - Laravel splits middleware
 * parameters on commas, so that silently passed 'manager' as the AUTH
 * GUARD name instead of a second allowed role, throwing "Auth guard
 * [manager] is not defined" on every hit. The fix is a pipe:
 * 'role:admin|manager'. This test locks that in.
 */
class RoleAccessTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_admin_can_reach_the_admin_dashboard(): void
    {
        $this->actingAs($this->staff('admin'))
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_manager_can_also_reach_the_admin_dashboard(): void
    {
        $this->actingAs($this->staff('manager'))
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_cashier_is_forbidden_from_the_admin_dashboard(): void
    {
        $this->actingAs($this->staff('cashier'))
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_cashier_can_reach_the_pos_sales_screen(): void
    {
        // Regression: the pos.* route group originally only granted
        // admin|manager|waiter, so a cashier - one of the roles the app
        // itself defines (App\Enums\Role) - had no accessible page at all
        // past login. See routes/web.php.
        $this->actingAs($this->staff('cashier'))
            ->get('/pos/sales')
            ->assertOk();
    }

    public function test_admin_can_reach_the_pos_sales_screen(): void
    {
        $this->actingAs($this->staff('admin'))
            ->get('/pos/sales')
            ->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/pos/sales')->assertRedirect('/login');
    }
}
