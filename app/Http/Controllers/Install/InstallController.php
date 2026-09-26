<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\PurchaseCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * The web-based installer Envato requires in place of manual .sql import +
 * hand-edited .env (see the build plan's compliance checklist). Steps:
 * welcome -> requirements -> purchase code -> database -> migrate/seed -> finish.
 * Each step is a simple GET (show) / POST (process) pair so it degrades
 * gracefully without any JS framework.
 */
class InstallController extends Controller
{
    private const REQUIRED_EXTENSIONS = [
        'bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml',
    ];

    public function welcome(): View
    {
        return view('install.welcome');
    }

    public function requirements(): View
    {
        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');

        $extensions = collect(self::REQUIRED_EXTENSIONS)
            ->mapWithKeys(fn ($ext) => [$ext => extension_loaded($ext)]);

        $writable = collect(['storage', 'bootstrap/cache'])
            ->mapWithKeys(fn ($path) => [$path => is_writable(base_path($path))]);

        $allOk = $phpOk && $extensions->every(fn ($ok) => $ok) && $writable->every(fn ($ok) => $ok);

        return view('install.requirements', compact('phpOk', 'extensions', 'writable', 'allOk'));
    }

    public function showPurchaseCode(): View
    {
        return view('install.purchase-code');
    }

    public function verifyPurchaseCode(Request $request, PurchaseCodeService $service): RedirectResponse
    {
        $data = $request->validate([
            'purchase_code' => ['required', 'string'],
            'envato_token' => ['required', 'string'],
        ]);

        $result = $service->verify($data['purchase_code'], $data['envato_token']);

        if (! $result['valid']) {
            return back()->withErrors(['purchase_code' => $result['message']]);
        }

        $request->session()->put('install.purchase_verified', true);
        $request->session()->put('install.envato_token', $data['envato_token']);

        return redirect()->route('install.database');
    }

    public function showDatabase(): View
    {
        abort_unless(session('install.purchase_verified'), 403, 'Verify your purchase code first.');

        return view('install.database');
    }

    /** Tests the DB connection live, writes .env, then runs migrate + seed. */
    public function storeDatabase(Request $request): RedirectResponse
    {
        abort_unless(session('install.purchase_verified'), 403);

        $data = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        config([
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
        ]);

        try {
            \DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            return back()->withErrors(['db_host' => 'Could not connect: '.$e->getMessage()]);
        }

        $this->writeEnv($data);

        Artisan::call('key:generate', ['--force' => true]);
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        return redirect()->route('install.finish');
    }

    public function finish(): View
    {
        File::put(storage_path('installed.lock'), now()->toDateTimeString());

        return view('install.finish');
    }

    /**
     * Writes the DB fields into .env. This handles two things a naive
     * string-replace easily gets wrong: (1) preg_replace() treats "$1" etc.
     * in the REPLACEMENT string as backreferences, so a password containing
     * one would get silently mangled if passed to it directly - callback
     * form sidesteps that; (2) a value with a space, "#", or quote would
     * either get truncated by .env's parser or break the line entirely, so
     * every value is quoted/escaped by envValue() rather than written raw.
     */
    private function writeEnv(array $db): void
    {
        $envPath = base_path('.env');
        $env = File::exists($envPath) ? File::get($envPath) : File::get(base_path('.env.example'));

        $replacements = [
            'DB_HOST' => $db['db_host'],
            'DB_PORT' => $db['db_port'],
            'DB_DATABASE' => $db['db_database'],
            'DB_USERNAME' => $db['db_username'],
            'DB_PASSWORD' => $db['db_password'] ?? '',
        ];

        foreach ($replacements as $key => $value) {
            $line = $key.'='.$this->envValue((string) $value);

            $env = preg_match("/^{$key}=.*/m", $env)
                ? preg_replace_callback("/^{$key}=.*/m", fn () => $line, $env)
                : $env."\n".$line;
        }

        File::put($envPath, $env);
    }

    /** Quotes/escapes a value for a single .env line - see writeEnv()'s note above. */
    private function envValue(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

        return $value === '' || preg_match('/[\s"#]/', $value)
            ? '"'.$escaped.'"'
            : $escaped;
    }
}
