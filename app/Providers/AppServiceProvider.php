<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

/**
 * The Laravel skeleton ships its own near-empty AppServiceProvider; merge
 * this in rather than overwrite if the skeleton's copy has other
 * registrations by the time you scaffold (see README setup steps).
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->applySettings();
    }

    /**
     * Admin > Settings values override .env at runtime, so a shop owner can
     * change the shop name, timezone and email (SMTP) details without
     * touching files on the server. Anything left blank falls back to .env.
     */
    private function applySettings(): void
    {
        $s = Setting::allValues();

        if ($s === []) {
            return;
        }

        if (! empty($s['shop_name'])) {
            config(['app.name' => $s['shop_name']]);
        }

        if (! empty($s['timezone']) && in_array($s['timezone'], timezone_identifiers_list(), true)) {
            config(['app.timezone' => $s['timezone']]);
            date_default_timezone_set($s['timezone']);
        }

        if (! empty($s['mail_mailer'])) {
            config(['mail.default' => $s['mail_mailer']]);
        }

        if (($s['mail_mailer'] ?? null) === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $s['mail_host'] ?? null,
                'mail.mailers.smtp.port' => (int) ($s['mail_port'] ?? 587),
                // Laravel 12: "smtps" = implicit SSL (465); "smtp" = plain or STARTTLS (587).
                'mail.mailers.smtp.scheme' => ($s['mail_encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.username' => $s['mail_username'] ?? null,
                'mail.mailers.smtp.password' => Setting::get('mail_password'),
            ]);
        }

        if (! empty($s['mail_from_address'])) {
            config(['mail.from.address' => $s['mail_from_address']]);
        }

        config(['mail.from.name' => $s['mail_from_name'] ?? null ?: config('app.name')]);
    }
}
