<?php

use Illuminate\Support\Facades\Facade;

return [
    // Old placeholder names in .env ("POS", "Restaurant POS", "Laravel") fall back to the product name.
    'name' => App\Support\Brand::resolve(env('APP_NAME')),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Written by the web installer's last step; while it's missing, visitors are
    // sent to /install (see RedirectIfNotInstalled) and /install stays open.
    'installed_lock' => storage_path('installed.lock'),
    'installer_redirect' => (bool) env('INSTALLER_REDIRECT', true),

    /*
     * Live-demo mode (your public preview site ONLY - never a buyer's shop).
     * Shows one-click demo logins, blocks account/settings changes, and lets
     * `php artisan demo:reset` wipe and reseed the database. Off by default.
     */
    'demo_mode' => (bool) env('DEMO_MODE', false),
];
