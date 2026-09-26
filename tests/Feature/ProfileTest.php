<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase, CreatesStaff;

    public function test_any_authenticated_role_can_view_the_profile_page(): void
    {
        // Cashier is deliberately not admin|manager - this page must not be
        // gated behind the admin|manager middleware Admin\StaffController uses.
        $user = $this->staff('cashier');

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('profile.edit'))->assertRedirect('/login');
    }

    public function test_user_can_update_their_profile_details(): void
    {
        $user = $this->staff('cashier', ['name' => 'Old Name']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'New Name',
                'email' => $user->email,
                'phone' => '01700000000',
            ])
            ->assertRedirect();

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('01700000000', $user->fresh()->phone);
    }

    public function test_profile_update_rejects_an_email_already_used_by_someone_else(): void
    {
        $user = $this->staff('cashier');
        $other = $this->staff('cashier');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $other->email,
                'phone' => null,
            ])
            ->assertSessionHasErrorsIn('profileUpdate', 'email');
    }

    public function test_user_can_update_their_password_with_the_correct_current_password(): void
    {
        $user = $this->staff('manager'); // UserFactory's default password is 'password'

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_password_update_is_rejected_with_the_wrong_current_password(): void
    {
        $user = $this->staff('manager');

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'not-the-right-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHasErrorsIn('passwordUpdate', 'current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
