<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Admin > Settings: shop details, receipt, timezone and outgoing email.
 * Admin-only (not manager) because it holds the SMTP password.
 *
 * Tax rate, currency, phone and address are mirrored onto the first Branch,
 * which BillingService already reads for the default tax rate.
 */
class SettingsController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        if (! Schema::hasTable('settings')) {
            return redirect()->route('admin.dashboard')
                ->withErrors(['settings' => 'Settings need a database update. Run "php artisan migrate" on the server, then open Settings again.']);
        }

        $branch = Branch::first();

        return view('admin.settings.edit', [
            'values' => [
                'shop_name' => Setting::get('shop_name', config('app.name')),
                'shop_phone' => Setting::get('shop_phone', $branch?->phone),
                'shop_email' => Setting::get('shop_email'),
                'shop_address' => Setting::get('shop_address', $branch?->address),
                'currency' => $branch?->currency ?? 'USD',
                'default_tax_rate' => $branch?->tax_rate ?? 0,
                'timezone' => Setting::get('timezone', config('app.timezone')),
                'receipt_footer' => Setting::get('receipt_footer'),
                'mail_mailer' => Setting::get('mail_mailer', config('mail.default') === 'smtp' ? 'smtp' : 'log'),
                'mail_host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
                'mail_port' => Setting::get('mail_port', config('mail.mailers.smtp.port')),
                'mail_encryption' => Setting::get('mail_encryption', 'ssl'),
                'mail_username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
                'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address')),
                'mail_from_name' => Setting::get('mail_from_name'),
            ],
            'hasMailPassword' => filled(Setting::get('mail_password')),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $smtp = $request->input('mail_mailer') === 'smtp';

        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:100'],
            'shop_phone' => ['nullable', 'string', 'max:50'],
            'shop_email' => ['nullable', 'email', 'max:150'],
            'shop_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'receipt_footer' => ['nullable', 'string', 'max:300'],
            'mail_mailer' => ['required', Rule::in(['smtp', 'log'])],
            'mail_host' => [$smtp ? 'required' : 'nullable', 'string', 'max:190'],
            'mail_port' => [$smtp ? 'required' : 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => [$smtp ? 'required' : 'nullable', Rule::in(['ssl', 'tls', 'none'])],
            'mail_username' => ['nullable', 'string', 'max:190'],
            'mail_password' => ['nullable', 'string', 'max:190'],
            'mail_from_address' => [$smtp ? 'required' : 'nullable', 'email', 'max:190'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
        ], [
            'mail_host.required' => 'Enter the SMTP host, or switch sending to "Don\'t send".',
            'mail_from_address.required' => 'Enter the address emails are sent from.',
        ]);

        $data['currency'] = Str::upper($data['currency']);

        // A blank password field means "keep the saved one".
        if (blank($data['mail_password'] ?? null)) {
            unset($data['mail_password']);
        }
        if ($request->boolean('clear_mail_password')) {
            $data['mail_password'] = null;
        }

        $branchFields = [
            'currency' => $data['currency'],
            'tax_rate' => $data['default_tax_rate'],
            'phone' => $data['shop_phone'],
            'address' => $data['shop_address'],
        ];
        $branch = Branch::first();
        $branch
            ? $branch->update($branchFields)
            : Branch::create(['name' => 'Main Branch', 'is_active' => true] + $branchFields);

        unset($data['currency'], $data['default_tax_rate']);
        Setting::put($data);

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['test_to' => ['required', 'email']]);

        if (config('mail.default') !== 'smtp') {
            return back()->withErrors(['test_to' => 'Email sending is off. Choose "Send with SMTP", save, then send a test.']);
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. If you can read this, password reset emails will arrive too.',
                fn ($m) => $m->to($data['test_to'])->subject('Test email from '.config('app.name'))
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'test_to' => 'Couldn\'t send the test email: '.Str::limit($e->getMessage(), 180).' Check host, port, encryption, username and password.',
            ]);
        }

        return back()->with('status', 'Test email sent to '.$data['test_to'].'. Check the inbox (and spam).');
    }
}
