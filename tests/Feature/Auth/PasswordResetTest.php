<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_link_is_emailed_to_active_staff(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', PasswordResetLinkController::SENT_MESSAGE);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_and_deactivated_emails_get_the_same_response_and_no_mail(): void
    {
        Notification::fake();
        $inactive = User::factory()->create(['is_active' => false]);

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status', PasswordResetLinkController::SENT_MESSAGE);
        $this->post('/forgot-password', ['email' => $inactive->email])
            ->assertSessionHas('status', PasswordResetLinkController::SENT_MESSAGE);

        Notification::assertNothingSent();
    }

    public function test_reset_screen_renders_and_password_can_be_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))->assertOk();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])->assertRedirect(route('login'))->assertSessionHas('status');

            $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));

            return true;
        });
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
