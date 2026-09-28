<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the public live demo (config app.demo_mode), blocks the few actions
 * that would lock other visitors out or touch real infrastructure:
 * settings (SMTP, license), staff accounts, your own profile/password, and
 * password resets. Everything else - sales, products, purchases, deleting
 * records - stays open so reviewers can try it; the hourly demo:reset
 * puts the data back.
 */
class DemoGuard
{
    public const MESSAGE = 'This action is disabled in the live demo. Everything else works, and the demo data resets every hour.';

    /** Route names that stay read-only in demo mode. */
    public const BLOCKED = [
        'admin.settings.update',
        'admin.settings.test-email',
        'admin.settings.license.deactivate',
        'admin.staff.store',
        'admin.staff.update',
        'admin.staff.destroy',
        'profile.update',
        'profile.password',
        'password.email',
        'password.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.demo_mode') || $request->isMethodSafe()) {
            return $next($request);
        }

        $name = $request->route()?->getName();

        if ($name === null || ! in_array($name, self::BLOCKED, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        // Password-reset forms show errors per field; admin pages show the first error.
        $field = str_starts_with($name, 'password.') ? 'email' : 'demo';

        return back()->withInput($request->except(['password', 'password_confirmation', 'current_password']))
            ->withErrors([$field => self::MESSAGE]);
    }
}
