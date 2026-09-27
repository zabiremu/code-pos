<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every web visitor to the /install wizard until storage/installed.lock
 * exists, so a fresh upload never shows a database error instead of the
 * installer. Switch off with INSTALLER_REDIRECT=false (the test suite does).
 */
class RedirectIfNotInstalled
{
    private const STATIC_ASSET = '/\.(css|js|map|json|png|jpe?g|gif|svg|webp|avif|ico|woff2?|ttf|eot|otf|txt|xml)$/i';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.installer_redirect')
            || file_exists(config('app.installed_lock'))
            || $request->is('install', 'install/*', 'up')
            || preg_match(self::STATIC_ASSET, $request->path())) {
            return $next($request);
        }

        return redirect()->route('install.welcome');
    }
}
