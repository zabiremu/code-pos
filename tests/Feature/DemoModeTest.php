<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoGuard;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * DEMO_MODE is for the public live demo only: one-click logins, a few
 * account/settings actions read-only, and an hourly demo:reset that must
 * refuse to run anywhere else.
 */
class DemoModeTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function demo(bool $on = true): void
    {
        config(['app.demo_mode' => $on]);
    }

    public function test_demo_mode_is_off_by_default(): void
    {
        $this->assertFalse((bool) config('app.demo_mode'));
    }

    public function test_login_page_shows_demo_accounts_only_in_demo_mode(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('manager@example.com');

        $this->demo();
        $this->get(route('login'))->assertOk()
            ->assertSee('admin@example.com')
            ->assertSee('manager@example.com')
            ->assertSee('cashier@example.com');
    }

    public function test_banner_shows_only_in_demo_mode(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get(route('admin.suppliers.index'))->assertOk()->assertDontSee('Live demo.');

        $this->demo();
        $this->actingAs($admin)->get(route('admin.suppliers.index'))->assertOk()->assertSee('Live demo.');
    }

    public function test_account_and_settings_changes_are_blocked_in_demo_mode(): void
    {
        $this->demo();
        $admin = $this->staff('admin', ['password' => 'original-pass']);
        $other = $this->staff('cashier', ['name' => 'Kept Name']);

        $this->actingAs($admin);

        $this->from(route('profile.edit'))
            ->put(route('profile.password'), ['current_password' => 'original-pass', 'password' => 'hijacked-1', 'password_confirmation' => 'hijacked-1'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors(['demo' => DemoGuard::MESSAGE]);
        $this->assertTrue(Hash::check('original-pass', $admin->fresh()->password));

        $this->put(route('admin.staff.update', $other), ['name' => 'Changed'])->assertSessionHasErrors('demo');
        $this->delete(route('admin.staff.destroy', $other))->assertSessionHasErrors('demo');
        $this->assertSame('Kept Name', $other->fresh()->name);

        $this->post(route('admin.staff.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'cashier', 'password' => 'password123'])
            ->assertSessionHasErrors('demo');
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);

        $this->put(route('admin.settings.update'), ['shop_name' => 'Hacked'])->assertSessionHasErrors('demo');
        $this->post(route('admin.settings.license.deactivate'))->assertSessionHasErrors('demo');
    }

    public function test_blocked_json_requests_get_a_403(): void
    {
        $this->demo();

        $this->actingAs($this->staff('admin'))
            ->putJson(route('admin.settings.update'), ['shop_name' => 'Hacked'])
            ->assertForbidden()
            ->assertJson(['message' => DemoGuard::MESSAGE]);
    }

    public function test_password_reset_emails_are_blocked_in_demo_mode(): void
    {
        $this->demo();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'admin@example.com'])
            ->assertSessionHasErrors(['email' => DemoGuard::MESSAGE]);
    }

    public function test_normal_work_is_allowed_in_demo_mode(): void
    {
        $this->demo();
        $this->actingAs($this->staff('admin'));

        $this->post(route('admin.suppliers.store'), ['name' => 'Acme Wholesale'])->assertSessionHasNoErrors();
        $supplier = Supplier::where('name', 'Acme Wholesale')->firstOrFail();

        // Deleting ordinary records stays allowed - the hourly reset restores them.
        $this->delete(route('admin.suppliers.destroy', $supplier))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_nothing_is_blocked_when_demo_mode_is_off(): void
    {
        $admin = $this->staff('admin');
        $other = $this->staff('cashier');

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $other))->assertSessionDoesntHaveErrors('demo');
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_every_blocked_route_name_exists(): void
    {
        foreach (DemoGuard::BLOCKED as $name) {
            $this->assertTrue(app('router')->has($name), "Route [{$name}] no longer exists - update DemoGuard::BLOCKED.");
        }
    }

    public function test_demo_seed_gives_each_demo_login_its_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('email', 'admin@example.com')->first()->hasRole('admin'));
        $this->assertTrue(User::where('email', 'manager@example.com')->first()->hasRole('manager'));
        $this->assertTrue(User::where('email', 'cashier@example.com')->first()->hasRole('cashier'));
    }
}
