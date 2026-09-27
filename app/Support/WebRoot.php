<?php

namespace App\Support;

/**
 * Lets the app run with the whole project uploaded into the web root or a
 * subfolder (example.com/pos/), not just with the domain pointed at public/.
 * Plain PHP: it runs in public/index.php before Laravel boots.
 */
final class WebRoot
{
    /**
     * The root .htaccess rewrites /pos/login to /pos/public/index.php, so PHP
     * reports SCRIPT_NAME=/pos/public/index.php while the visitor asked for
     * /pos/login. Symfony finds no common prefix, falls back to a base URL of
     * "" and routes "/pos/login" (a 404), and every generated URL loses /pos.
     * Reporting the script at the URL the visitor actually uses -
     * /pos/index.php - gives a base URL of "/pos" and no "/public" anywhere.
     *
     * @param  array<string, mixed>  $server  $_SERVER
     * @return array<string, mixed>
     */
    public static function normalize(array $server): array
    {
        $script = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));

        if (! preg_match('#^(.*)/public/index\.php$#', $script, $m)) {
            return $server; // document root is public/ (or the root index.php fallback)
        }

        $projectBase = $m[1]; // "" in the web root, "/pos" in a subfolder
        $path = rawurldecode(explode('?', (string) ($server['REQUEST_URI'] ?? '/'), 2)[0]);

        // The visitor typed a /public/ URL themselves; that still works as-is.
        if ($path === $projectBase.'/public' || str_starts_with($path, $projectBase.'/public/')) {
            return $server;
        }

        $server['SCRIPT_NAME'] = $projectBase.'/index.php';
        $server['PHP_SELF'] = $projectBase.'/index.php';

        // "/pos" (no slash - Apache normally redirects it first) shares no prefix with
        // "/pos/index.php" either; treat it as the folder's home page, "/pos/".
        if ($projectBase !== '' && $path === $projectBase) {
            $query = explode('?', (string) $server['REQUEST_URI'], 2)[1] ?? null;
            $server['REQUEST_URI'] = $projectBase.'/'.($query !== null ? '?'.$query : '');
        }

        return $server;
    }

    /**
     * For the root index.php fallback (no mod_rewrite): pages are reached as
     * /pos/index.php/login, and assets are served straight out of public/.
     *
     * @param  array<string, mixed>  $server  $_SERVER
     * @return array{0: array<string, mixed>, 1: string} [$server, asset URL]
     */
    public static function withoutRewrite(array $server): array
    {
        $base = rtrim(str_replace('\\', '/', dirname((string) ($server['SCRIPT_NAME'] ?? '/index.php'))), '/');
        [$path, $query] = array_pad(explode('?', (string) ($server['REQUEST_URI'] ?? '/'), 2), 2, null);

        // "/pos/" has no index.php in it, so Laravel would build links like
        // /pos/login that only work with mod_rewrite. Make it /pos/index.php/.
        if ($path === $base || $path === $base.'/') {
            $server['REQUEST_URI'] = $base.'/index.php/'.($query !== null ? '?'.$query : '');
        }

        return [$server, $base.'/public'];
    }
}
