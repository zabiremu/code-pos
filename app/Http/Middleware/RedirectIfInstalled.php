<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the /install wizard once storage/installed.lock exists, so it can
 * never be re-run against a live site. Register on the install route group
 * in bootstrap/app.php (see README).
 */
class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        return $next($request);
    }
}
