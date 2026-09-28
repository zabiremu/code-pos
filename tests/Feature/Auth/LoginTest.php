<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_staff_can_log_in_with_valid_credentials(): void
    {
        $user = $this->staff('admin'); // UserFactory's default password is 'password'

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_managers_land_on_the_dashboard_and_cashiers_on_the_register(): void
    {
        $manager = $this->staff('manager');
        $this->post('/login', ['email' => $manager->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));

        // Cashiers can't open the dashboard; sending them there was a 403 on every login.
        $cashier = $this->staff('cashier');
        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])
            ->assertRedirect(route('pos.register'));
        $this->get(route('pos.register'))->assertOk()->assertDontSee(route('admin.dashboard'));
    }

    public function test_signed_in_users_opening_home_or_login_are_not_stuck_in_a_redirect_loop(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs($this->staff('admin'));
        $this->get('/')->assertRedirect(route('admin.dashboard'));
        $this->get(route('login'))->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->staff('cashier'));
        $this->get('/')->assertRedirect(route('pos.register'));
        $this->get(route('login'))->assertRedirect(route('pos.register'));
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_staff_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login_from_protected_routes(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_staff_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        // The 6th attempt is blocked by the throttle before credentials are
        // even checked - even with the CORRECT password this time.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
