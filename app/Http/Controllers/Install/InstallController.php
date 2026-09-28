<?php

namespace App\Http\Controllers\Install;

use App\Enums\Role as PosRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\PurchaseCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\InstallSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * The web-based installer Envato requires in place of manual .sql import +
 * hand-edited .env (see the build plan's compliance checklist). Steps:
 * welcome -> requirements -> purchase code -> database (migrate + roles) ->
 * admin account (the buyer's own login, optional sample data) -> finish.
 * Each step is a simple GET (show) / POST (process) pair so it degrades
 * gracefully without any JS framework.
 *
 * Every step checks server-side that the one before it passed (install.*
 * session keys), so the wizard can't be skipped by typing a URL. .env and
 * APP_KEY are created before Laravel boots - see App\Support\InstallerBootstrap.
 */
class InstallController extends Controller
{
    public const NAME_HINT = 'Use only letters, numbers and underscores (e.g. myuser_pos). Hyphens and spaces aren\'t allowed.';

    private const REQUIRED_EXTENSIONS = [
        'bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml',
    ];

    public function welcome(): View
    {
        return view('install.welcome');
    }

    public function requirements(Request $request): View
    {
        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');

        $extensions = collect(self::REQUIRED_EXTENSIONS)
            ->mapWithKeys(fn ($ext) => [$ext => extension_loaded($ext)]);

        $writable = collect(['storage', 'bootstrap/cache'])
            ->mapWithKeys(fn ($path) => [$path => is_writable(base_path($path))])
            ->put('.env', is_writable(app()->environmentFilePath()));

        $allOk = $phpOk && $extensions->every(fn ($ok) => $ok) && $writable->every(fn ($ok) => $ok);

        $request->session()->put('install.requirements_ok', $allOk);

        return view('install.requirements', compact('phpOk', 'extensions', 'writable', 'allOk'));
    }

    public function showPurchaseCode(): View|RedirectResponse
    {
        if (! session('install.requirements_ok')) {
            return redirect()->route('install.requirements');
        }

        return view('install.purchase-code');
    }

    public function verifyPurchaseCode(Request $request, PurchaseCodeService $service): RedirectResponse
    {
        if (! session('install.requirements_ok')) {
            return redirect()->route('install.requirements');
        }

        $data = $request->validate([
            'purchase_code' => ['required', 'string', 'max:64'],
        ]);

        $result = $service->verify($data['purchase_code'], $request->getHost());

        if (! $result['valid']) {
            return back()->withInput()->withErrors(['purchase_code' => $result['message']]);
        }

        $service->store($data['purchase_code'], $request->getHost(), $result);
        $request->session()->put('install.purchase_verified', true);

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
        abort_unless(session('install.purchase_verified'), 403, 'Verify your purchase code first.');

        $data = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'integer'],
            'db_database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            'db_username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            'db_password' => ['nullable', 'string'],
        ], [
            'db_database.regex' => self::NAME_HINT,
            'db_username.regex' => self::NAME_HINT,
        ]);

        // Forget any connection made with earlier settings, or the test below
        // would silently reuse it instead of trying the new credentials.
        DB::purge('mysql');
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
        ]);

        // .env is only written once this succeeds, so a failed attempt leaves it untouched.
        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            return back()
                ->withInput($request->except('db_password'))
                ->withErrors($this->connectionError($e, $data));
        }

        $this->writeEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ]);

        // No key:generate here: APP_KEY was created once before boot, and
        // rotating it now would make the buyer's session cookie unreadable.
        // InstallSeeder: roles + default branch only. No users - the next
        // step creates the buyer's own admin, so no install has a known login.
        try {
            $commands = [
                'migrate' => ['--force' => true],
                'db:seed' => ['--class' => InstallSeeder::class, '--force' => true],
            ];
            foreach ($commands as $command => $options) {
                if (Artisan::call($command, $options) !== 0) {
                    throw new \RuntimeException(trim(Artisan::output()) ?: "{$command} failed.");
                }
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->except('db_password'))->withErrors([
                'db_host' => 'Connected, but setting up the tables failed: '.$e->getMessage()
                    .' Check that the database is empty and this user can create tables, then try again.',
            ]);
        }

        $request->session()->put('install.database_done', true);

        return redirect()->route('install.admin');
    }

    public function showAdmin(): View|RedirectResponse
    {
        abort_unless(session('install.database_done'), 403, 'Finish the database step first.');

        if (session('install.admin_done')) {
            return redirect()->route('install.finish');
        }

        return view('install.admin');
    }

    /** Creates the buyer's own admin login (and, if asked, the sample catalog). */
    public function storeAdmin(Request $request): RedirectResponse
    {
        abort_unless(session('install.database_done'), 403, 'Finish the database step first.');

        // A double-submit or back-button must not create a second admin.
        if (session('install.admin_done')) {
            return redirect()->route('install.finish');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'sample_data' => ['nullable', 'boolean'],
        ]);

        // Roles were seeded in the previous request; drop Spatie's cached
        // (possibly empty) role list so assignRole() can find them.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $branch = Branch::firstOrCreate(['name' => 'Main Branch'], ['tax_rate' => 0, 'currency' => 'USD', 'is_active' => true]);

        // The model's 'hashed' cast hashes the password; email_verified_at
        // isn't mass-assignable, hence forceFill.
        $admin = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole(PosRole::Admin->value);

        if ($request->boolean('sample_data')) {
            try {
                Artisan::call('db:seed', ['--class' => DemoProductSeeder::class, '--force' => true]);
            } catch (\Throwable $e) {
                // Sample data is a nice-to-have: the install itself succeeded.
                report($e);
            }
        }

        $request->session()->put('install.admin_done', true);
        $request->session()->put('install.admin_email', $admin->email);

        return redirect()->route('install.finish');
    }

    public function finish(Request $request): View
    {
        abort_unless(session('install.database_done'), 403, 'Finish the database step first.');
        abort_unless(session('install.admin_done'), 403, 'Create your admin account first.');

        $adminEmail = session('install.admin_email');

        File::put(config('app.installed_lock'), now()->toDateTimeString());
        // root() keeps a subfolder (http://example.com/pos); drop the "/index.php" it
        // carries on hosts without mod_rewrite (see the root index.php).
        $this->writeEnv(['APP_URL' => preg_replace('#/index\.php$#', '', $request->root())]);

        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            Artisan::call($command);
        }

        $request->session()->forget('install');

        return view('install.finish', ['adminEmail' => $adminEmail]);
    }

    /**
     * Turns a failed MySQL connection into something a buyer on cPanel can act
     * on. The driver error code is in getCode() or, depending on the PDO
     * driver, only inside the message ("SQLSTATE[HY000] [1045] ...").
     *
     * @param  array<string, mixed>  $db
     * @return array<string, string> field => message
     */
    private function connectionError(\Throwable $e, array $db): array
    {
        $message = $e->getMessage();
        $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 0;
        if (preg_match('/\[(\d{4})\]/', $message, $m)) {
            $code = (int) $m[1];
        }

        return match (true) {
            $code === 1049 => ['db_database' => "Database '{$db['db_database']}' doesn't exist. Create it first in cPanel > MySQL Databases (or phpMyAdmin), then try again."],
            // cPanel users only see their own databases, so a typo'd or
            // not-yet-created name, or a user not added to it, comes back as
            // 1044 rather than 1049.
            $code === 1044 => ['db_database' => "User '{$db['db_username']}' can't open database '{$db['db_database']}'. Check the name is exactly right, that the database exists, and that this user is added to it (cPanel > MySQL Databases > Add User To Database, ALL PRIVILEGES)."],
            $code === 1045 => ['db_username' => "Username or password is wrong, or this user isn't added to the database (cPanel > MySQL Databases > Add User To Database, ALL PRIVILEGES)."],
            in_array($code, [2002, 2003, 2005], true) || stripos($message, 'connection refused') !== false => [
                'db_host' => "Can't reach the MySQL server at {$db['db_host']}:{$db['db_port']}. On cPanel the host is usually 'localhost'.",
            ],
            default => $this->unknownConnectionError($e, $db),
        };
    }

    /** @param  array<string, mixed>  $db */
    private function unknownConnectionError(\Throwable $e, array $db): array
    {
        Log::warning('Installer could not connect to MySQL: '.$e->getMessage(), [
            'host' => $db['db_host'], 'port' => $db['db_port'], 'database' => $db['db_database'], 'username' => $db['db_username'],
        ]);

        return ['db_host' => 'Could not connect to the database with these details. Double-check them, or ask your host for the correct MySQL host and port. The exact error was saved to storage/logs.'];
    }

    /**
     * Writes KEY=value pairs into .env. This handles two things a naive
     * string-replace easily gets wrong: (1) preg_replace() treats "$1" etc.
     * in the REPLACEMENT string as backreferences, so a password containing
     * one would get silently mangled if passed to it directly - callback
     * form sidesteps that; (2) a value with a space, "#", or quote would
     * either get truncated by .env's parser or break the line entirely, so
     * every value is quoted/escaped by envValue() rather than written raw.
     *
     * @param  array<string, scalar>  $values
     */
    private function writeEnv(array $values): void
    {
        $envPath = app()->environmentFilePath();
        $env = File::exists($envPath) ? File::get($envPath) : File::get(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->envValue((string) $value);

            $env = preg_match("/^{$key}=.*/m", $env)
                ? preg_replace_callback("/^{$key}=.*/m", fn () => $line, $env)
                : rtrim($env, "\r\n")."\n".$line."\n";
        }

        // Temp file + rename: a failed write can't leave a half-written .env behind.
        File::replace($envPath, $env);
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
