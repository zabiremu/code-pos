<?php

namespace Tests\Unit;

use App\Support\InstallerBootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The pre-boot step that makes a fresh upload (no .env, no APP_KEY) able to
 * reach /install. Every test works in its own temp dir.
 */
class InstallerBootstrapTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'installer-bootstrap-'.uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir.'/.env.example', "APP_NAME=Shop\nAPP_KEY=\nSESSION_DRIVER=database\n");
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
        parent::tearDown();
    }

    private function env(): string
    {
        return (string) file_get_contents($this->dir.'/.env');
    }

    private function key(): string
    {
        preg_match('/^APP_KEY=(.*)$/m', $this->env(), $m);

        return $m[1] ?? '';
    }

    public function test_creates_env_from_the_example_and_fills_the_empty_key(): void
    {
        $this->assertTrue(InstallerBootstrap::ensureEnvFile($this->dir));

        $this->assertStringContainsString("APP_NAME=Shop\n", $this->env());
        $this->assertStringContainsString("SESSION_DRIVER=database\n", $this->env());
        $this->assertStringStartsWith('base64:', $this->key());
        $this->assertSame(32, strlen(base64_decode(substr($this->key(), 7), true)));
        $this->assertSame(1, substr_count($this->env(), 'APP_KEY='));
    }

    public function test_never_overwrites_an_existing_key(): void
    {
        file_put_contents($this->dir.'/.env', "APP_KEY=base64:keepme\nOTHER=1\n");

        $this->assertTrue(InstallerBootstrap::ensureEnvFile($this->dir));
        $this->assertTrue(InstallerBootstrap::ensureEnvFile($this->dir));

        $this->assertSame("APP_KEY=base64:keepme\nOTHER=1\n", $this->env());
    }

    public function test_fills_a_quoted_empty_key_and_leaves_other_lines_alone(): void
    {
        file_put_contents($this->dir.'/.env', "A=1\nAPP_KEY=\"\"\nB=\"x y\"\n");

        InstallerBootstrap::ensureEnvFile($this->dir);

        $this->assertMatchesRegularExpression('/^A=1\nAPP_KEY=base64:\S{44}\nB="x y"\n$/', $this->env());
    }

    public function test_appends_a_key_when_the_line_is_missing(): void
    {
        file_put_contents($this->dir.'/.env', "APP_NAME=Shop\n");

        InstallerBootstrap::ensureEnvFile($this->dir);

        $this->assertStringStartsWith("APP_NAME=Shop\nAPP_KEY=base64:", $this->env());
    }

    public function test_prepare_creates_env_and_storage_folders_on_a_fresh_copy(): void
    {
        $this->assertSame([], InstallerBootstrap::prepare($this->dir));

        $this->assertFileExists($this->dir.'/.env');
        $this->assertStringStartsWith('base64:', $this->key());
        $this->assertDirectoryExists($this->dir.'/storage/framework/sessions');
        $this->assertDirectoryExists($this->dir.'/bootstrap/cache');
    }

    public function test_prepare_does_nothing_once_installed(): void
    {
        mkdir($this->dir.'/storage');
        file_put_contents($this->dir.'/storage/installed.lock', 'installed');

        $this->assertSame([], InstallerBootstrap::prepare($this->dir));

        $this->assertFileDoesNotExist($this->dir.'/.env');
        $this->assertDirectoryDoesNotExist($this->dir.'/bootstrap');
    }

    public function test_install_drivers_never_override_an_already_set_variable(): void
    {
        // phpunit.xml sets these - they must survive, which is also what keeps
        // a server-level SESSION_DRIVER in charge on real hosting.
        $before = getenv('SESSION_DRIVER');
        $this->assertNotFalse($before);

        InstallerBootstrap::useInstallDrivers();

        $this->assertSame($before, getenv('SESSION_DRIVER'));
    }

    public function test_the_test_run_itself_uses_phpunit_drivers_not_the_install_ones(): void
    {
        // `php artisan test` boots the app in a parent process first; on a copy
        // without installed.lock the pre-boot must not leak file drivers into us.
        $this->assertSame('array', getenv('CACHE_STORE'));
        $this->assertSame('array', getenv('SESSION_DRIVER'));
    }
}
