<?php

namespace Tests\Feature;

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * The installer's database step: buyers get an actionable message instead of
 * a raw MySQL error, and .env is never touched unless the connection works.
 * .env and the lock live in a temp dir - never the real ones.
 */
class InstallerDatabaseStepTest extends TestCase
{
    private const ENV = "APP_NAME=Test\nDB_HOST=127.0.0.1\nDB_DATABASE=pos\nDB_USERNAME=root\nDB_PASSWORD=\n";

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/installer-db-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        File::put($this->dir.'/.env', self::ENV);
        $this->app->useEnvironmentPath($this->dir);
        config(['app.installed_lock' => $this->dir.'/installed.lock']);

        $this->withSession(['install.purchase_verified' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function form(array $overrides = []): array
    {
        return array_merge(['db_host' => 'localhost', 'db_port' => 3306, 'db_database' => 'shop_pos', 'db_username' => 'shop_user', 'db_password' => 'secret'], $overrides);
    }

    /** Makes the connection test throw the way PDO does, with the driver code in the message. */
    private function failConnection(int $code, string $text): void
    {
        DB::shouldReceive('purge')->with('mysql');
        $this->throwOnConnect(new \PDOException("SQLSTATE[HY000] [{$code}] {$text}", $code));
        Artisan::shouldReceive('call')->never();
    }

    private function throwOnConnect(\Throwable $e): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('getPdo')->andThrow($e);
        DB::shouldReceive('connection')->with('mysql')->andReturn($connection);
    }

    private function submit(array $overrides = [])
    {
        return $this->from(route('install.database'))->post(route('install.database.store'), $this->form($overrides));
    }

    private function assertEnvUntouched(): void
    {
        $this->assertSame(self::ENV, File::get($this->dir.'/.env'));
        // No half-written temp file left next to it either.
        $this->assertSame(['.env'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
    }

    public function test_unknown_database_names_the_database_and_where_to_create_it(): void
    {
        $this->failConnection(1049, "Unknown database 'shop_pos'");

        $this->submit()
            ->assertRedirect(route('install.database'))
            ->assertSessionHasErrors(['db_database' => "Database 'shop_pos' doesn't exist. Create it first in cPanel > MySQL Databases (or phpMyAdmin), then try again."])
            ->assertSessionMissing('install.database_done');

        $this->assertEnvUntouched();
    }

    public function test_no_access_to_the_database_explains_both_likely_causes(): void
    {
        $this->failConnection(1044, "Access denied for user 'shop_user'@'localhost' to database 'shop_pos'");

        $this->submit()
            ->assertSessionHasErrors(['db_database' => "User 'shop_user' can't open database 'shop_pos'. Check the name is exactly right, that the database exists, and that this user is added to it (cPanel > MySQL Databases > Add User To Database, ALL PRIVILEGES)."])
            ->assertSessionMissing('install.database_done');

        $this->assertEnvUntouched();
    }

    public function test_access_denied_explains_the_add_user_to_database_step(): void
    {
        $this->failConnection(1045, "Access denied for user 'shop_user'@'localhost' (using password: YES)");

        $this->submit()->assertSessionHasErrors(['db_username' => "Username or password is wrong, or this user isn't added to the database (cPanel > MySQL Databases > Add User To Database, ALL PRIVILEGES)."]);

        $this->assertEnvUntouched();
    }

    public function test_unreachable_server_names_the_host_and_port(): void
    {
        $this->failConnection(2002, 'No connection could be made because the target machine actively refused it');

        $this->submit(['db_host' => '10.0.0.9', 'db_port' => 3307])
            ->assertSessionHasErrors(['db_host' => "Can't reach the MySQL server at 10.0.0.9:3307. On cPanel the host is usually 'localhost'."]);

        $this->assertEnvUntouched();
    }

    public function test_connection_refused_without_a_code_is_treated_as_unreachable(): void
    {
        DB::shouldReceive('purge')->with('mysql');
        $this->throwOnConnect(new \PDOException('SQLSTATE[HY000]: Connection refused'));

        $this->submit()->assertSessionHasErrors(['db_host' => "Can't reach the MySQL server at localhost:3306. On cPanel the host is usually 'localhost'."]);
    }

    public function test_any_other_error_is_generic_on_screen_and_logged_in_full(): void
    {
        $this->failConnection(2054, 'The server requested authentication method unknown to the client');
        Log::spy();

        $response = $this->submit()->assertSessionHasErrors('db_host');

        $this->assertStringNotContainsString('2054', session('errors')->first('db_host'));
        $this->assertStringContainsString('storage/logs', session('errors')->first('db_host'));
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => str_contains($message, '[2054] The server requested authentication method')
            && $context['host'] === 'localhost' && ! array_key_exists('password', $context));
        $response->assertSessionMissing('install.database_done');

        $this->assertEnvUntouched();
    }

    public function test_the_password_is_never_flashed_back_to_the_session(): void
    {
        $this->failConnection(1045, 'Access denied');

        $this->submit()->assertSessionHasInput('db_username', 'shop_user');

        $this->assertNull(session()->getOldInput('db_password'));
    }

    public function test_names_with_hyphens_or_spaces_are_rejected_before_connecting(): void
    {
        DB::shouldReceive('connection')->never();

        foreach (['db_database' => ['shop-pos', 'my shop', str_repeat('a', 65)], 'db_username' => ['shop-user', 'user name', 'user;drop']] as $field => $values) {
            foreach ($values as $value) {
                $expected = strlen($value) > 64 ? 'The '.str_replace('_', ' ', $field).' field must not be greater than 64 characters.' : InstallController::NAME_HINT;
                $this->submit([$field => $value])->assertSessionHasErrors([$field => $expected]);
            }
        }

        $this->assertEnvUntouched();
    }

    public function test_valid_names_pass_validation(): void
    {
        DB::shouldReceive('purge')->with('mysql');
        DB::shouldReceive('connection')->with('mysql')->andReturn(Mockery::mock(['getPdo' => true]));
        Artisan::shouldReceive('call')->andReturn(0);

        $this->submit(['db_database' => 'cpuser_POS2', 'db_username' => str_repeat('u', 64)])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('install.admin'));

        $env = File::get($this->dir.'/.env');
        $this->assertStringContainsString("DB_DATABASE=cpuser_POS2\n", $env);
        $this->assertStringContainsString('DB_USERNAME='.str_repeat('u', 64)."\n", $env);
    }

    public function test_the_view_explains_the_naming_rule(): void
    {
        $this->get(route('install.database'))->assertOk()->assertSee(InstallController::NAME_HINT, escape: true);
    }
}
