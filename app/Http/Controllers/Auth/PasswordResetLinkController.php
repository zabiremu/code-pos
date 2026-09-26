<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Step 1 of "forgot password": email a signed reset link.
 *
 * The response is identical whether or not the email belongs to an account
 * (or the account is deactivated), so this form can't be used to discover
 * which staff emails exist.
 */
class PasswordResetLinkController extends Controller
{
    public const SENT_MESSAGE = 'If that email belongs to an active staff account, a reset link is on its way. It expires in 60 minutes.';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->is_active) {
            // Result intentionally ignored (sent / throttled / invalid user)
            // so every outcome looks the same to the requester.
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', self::SENT_MESSAGE);
    }
}
