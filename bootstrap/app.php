<?php

use App\Http\Middleware\RedirectIfInstalled;
use App\Http\Middleware\RedirectIfNotInstalled;
use App\Support\InstallerBootstrap;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

// Before .env is loaded: on a not-yet-installed copy, create .env + APP_KEY
// and switch to file-based drivers so the /install wizard can boot.
InstallerBootstrap::run(dirname(__DIR__));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // The installer wizard and the login/logout routes each live on
            // their own route file (see routes/install.php, routes/auth.php).
            Route::middleware('web')->group(base_path('routes/install.php'));
            Route::middleware('web')->group(base_path('routes/auth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'redirect.if.installed' => RedirectIfInstalled::class,
        ]);
        // Prepended so nothing else (session, auth, DB) runs on a not-installed copy.
        $middleware->web(prepend: [RedirectIfNotInstalled::class]);
        $middleware->web(append: [\App\Http\Middleware\DemoGuard::class]);
        // A signed-in user opening /login etc. goes to their role's home,
        // not "/" (which used to bounce straight back to /login in a loop).
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => \App\Support\HomeRoute::for($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
