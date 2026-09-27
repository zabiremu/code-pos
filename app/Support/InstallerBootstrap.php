<?php

namespace App\Support;

/**
 * Runs from bootstrap/app.php BEFORE Laravel loads .env, so a fresh upload
 * (no .env, no APP_KEY, no database) can still render the /install wizard.
 * Plain PHP on purpose: nothing from the framework is available yet.
 *
 * Once storage/installed.lock exists this does nothing at all.
 */
final class InstallerBootstrap
{
    /**
     * Drivers that work before a database exists. .env.example ships with
     * `database` for all three, which would crash the installer's first page.
     */
    public const INSTALL_DRIVERS = [
        'SESSION_DRIVER' => 'file',
        'CACHE_STORE' => 'file',
        'QUEUE_CONNECTION' => 'sync',
    ];

    /** Folders Laravel writes to on every request, relative to the base path. */
    private const WRITABLE_DIRS = [
        'storage',
        'storage/app',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ];

    public static function run(string $basePath): void
    {
        $problems = self::prepare($basePath);

        // On the CLI (composer scripts, artisan) just carry on; the web page is for buyers.
        if ($problems !== [] && PHP_SAPI !== 'cli') {
            self::renderProblems($basePath, $problems);
            exit;
        }
    }

    /**
     * @return list<string> paths that need to be writable (empty when all is well)
     */
    public static function prepare(string $basePath): array
    {
        $basePath = rtrim($basePath, '/\\');

        if (is_file($basePath.'/storage/installed.lock')) {
            return [];
        }

        $problems = self::unwritableDirs($basePath);

        if (! self::ensureEnvFile($basePath)) {
            $problems[] = '.env';
        }

        // Web only: putenv() is inherited by child processes, so on the CLI it
        // would leak into e.g. the phpunit process `php artisan test` spawns
        // and override phpunit.xml's array cache/session drivers.
        if (PHP_SAPI !== 'cli') {
            self::useInstallDrivers();
        }

        return $problems;
    }

    /**
     * Creates .env from .env.example when missing and fills an empty APP_KEY.
     * An existing key is never replaced - rotating it would log everyone out
     * and make encrypted data unreadable.
     *
     * @return bool false when .env can't be created or written
     */
    public static function ensureEnvFile(string $basePath): bool
    {
        $envPath = rtrim($basePath, '/\\').'/.env';

        if (! is_file($envPath)) {
            $example = dirname($envPath).'/.env.example';
            $contents = is_file($example) ? (string) file_get_contents($example) : "APP_KEY=\n";
            if (@file_put_contents($envPath, $contents, LOCK_EX) === false) {
                return false;
            }
        }

        if (! is_writable($envPath)) {
            return false;
        }

        $env = (string) file_get_contents($envPath);

        if (preg_match('/^APP_KEY=(.*)$/m', $env, $m)) {
            if (trim($m[1], " \t\r\"'") !== '') {
                return true;
            }
            // Callback form so a "$" in the key can never be read as a backreference.
            $line = 'APP_KEY='.self::newKey();
            $env = preg_replace_callback('/^APP_KEY=.*$/m', fn () => $line, $env, 1);
        } else {
            $env = rtrim($env, "\r\n")."\nAPP_KEY=".self::newKey()."\n";
        }

        return @file_put_contents($envPath, $env, LOCK_EX) !== false;
    }

    /**
     * Sets the install-time drivers in the process environment. Dotenv never
     * overwrites a variable that is already set, so these win over .env - but
     * anything the server (or phpunit.xml) already set is left alone.
     */
    public static function useInstallDrivers(): void
    {
        foreach (self::INSTALL_DRIVERS as $key => $value) {
            if (getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key])) {
                continue;
            }
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public static function newKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /** @return list<string> */
    private static function unwritableDirs(string $basePath): array
    {
        $problems = [];
        foreach (self::WRITABLE_DIRS as $dir) {
            $path = $basePath.'/'.$dir;
            if (! is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            if (! is_dir($path) || ! is_writable($path)) {
                $problems[] = $dir;
            }
        }

        return $problems;
    }

    /** @param  list<string>  $problems */
    private static function renderProblems(string $basePath, array $problems): void
    {
        if (! headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
        }

        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $rows = '';
        foreach ($problems as $path) {
            $hint = $path === '.env'
                ? 'file must be writable (chmod 664), or its folder must be writable so it can be created'
                : 'folder must exist and be writable (chmod 775, including everything inside it)';
            $rows .= '<li><code>'.$e($path).'</code> - '.$e($hint).'</li>';
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Installation - file permissions</title>'
            .'<style>body{font-family:system-ui,sans-serif;background:#f4f4f5;color:#18181b;margin:0;padding:16px}'
            .'main{max-width:640px;margin:48px auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.1)}'
            .'h1{font-size:20px;margin:0 0 12px}li{margin:8px 0}code{background:#f4f4f5;padding:2px 6px;border-radius:4px;word-break:break-all}'
            .'p{line-height:1.5}</style></head><body><main>'
            .'<h1>Almost there - a few permissions need fixing</h1>'
            .'<p>The installer needs to write to these paths inside <code>'.$e($basePath).'</code>:</p>'
            .'<ul>'.$rows.'</ul>'
            .'<p>In cPanel, open <strong>File Manager</strong>, right-click each item, choose '
            .'<strong>Change Permissions</strong>, then reload this page.</p>'
            .'</main></body></html>';
    }
}
