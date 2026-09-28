<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * The wizard must be walked in order, server-side: skipping to a later URL
 * gets a redirect or 403, and only a finished database step can write the
 * lock. Uses a temp dir for .env and installed.lock - never the real ones.
 */
class InstallerStepsTest extends TestCase
{
    private string $dir;

    private string $lock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/installer-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        File::put($this->dir.'/.env', "APP_NAME=Test\nAPP_URL=http://localhost\nDB_HOST=127.0.0.1\n");
        $this->app->useEnvironmentPath($this->dir);

        $this->lock = $this->dir.'/installed.lock';
        config([
            'app.installed_lock' => $this->lock,
            'license.server' => 'https://license.test',
            'license.file' => $this->dir.'/license.json',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function dbForm(): array
    {
        return ['db_host' => 'db.local', 'db_port' => 3306, 'db_database' => 'shop', 'db_username' => 'shop', 'db_password' => 'p@ss $1 word'];
    }

    /** Pretends the MySQL connection works and each Artisan command returns $migrateResult. */
    private function fakeDatabase(\Closure|int $migrateResult = 0): void
    {
        DB::shouldReceive('purge')->with('mysql');
        DB::shouldReceive('connection')->with('mysql')->andReturn(Mockery::mock(['getPdo' => true]));

        $call = Artisan::shouldReceive('call')->with('migrate', ['--force' => true]);
        $migrateResult instanceof \Closure ? $call->andReturnUsing($migrateResult) : $call->andReturn($migrateResult);
        Artisan::shouldReceive('call')->with('db:seed', ['--class' => \Database\Seeders\InstallSeeder::class, '--force' => true])->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('');
    }

    public function test_requirements_page_records_the_result_in_the_session(): void
    {
        $response = $this->get(route('install.requirements'))->assertOk();

        $response->assertSessionHas('install.requirements_ok', $response->viewData('allOk'));
    }

    public function test_purchase_code_step_redirects_until_requirements_pass(): void
    {
        Http::fake();

        $this->get(route('install.purchase-code'))->assertRedirect(route('install.requirements'));
        $this->post(route('install.purchase-code.verify'), ['purchase_code' => 'aaaaaaaa-1111-2222-3333-444444444444'])
            ->assertRedirect(route('install.requirements'));

        $this->withSession(['install.requirements_ok' => false])
            ->get(route('install.purchase-code'))->assertRedirect(route('install.requirements'));

        Http::assertNothingSent();
    }

    public function test_database_step_is_forbidden_until_the_purchase_code_is_verified(): void
    {
        $this->withSession(['install.requirements_ok' => true]);

        $this->get(route('install.database'))->assertForbidden();
        $this->post(route('install.database.store'), $this->dbForm())->assertForbidden();
        $this->assertStringNotContainsString('db.local', File::get($this->dir.'/.env'));
    }

    public function test_finish_on_a_fresh_app_is_forbidden_and_writes_no_lock(): void
    {
        $this->get(route('install.finish'))->assertForbidden();

        $this->withSession(['install.requirements_ok' => true, 'install.purchase_verified' => true])
            ->get(route('install.finish'))->assertForbidden();

        $this->assertFileDoesNotExist($this->lock);
    }

    public function test_admin_step_and_finish_are_forbidden_until_the_database_step_is_done(): void
    {
        $this->withSession(['install.requirements_ok' => true, 'install.purchase_verified' => true]);

        $this->get(route('install.admin'))->assertForbidden();
        $this->post(route('install.admin.store'), ['name' => 'X', 'email' => 'x@shop.test', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertForbidden();

        // Database done but no admin yet: finish is still closed.
        $this->withSession(['install.database_done' => true])->get(route('install.finish'))->assertForbidden();
        $this->assertFileDoesNotExist($this->lock);
    }

    public function test_a_failed_migration_shows_a_friendly_error_and_does_not_unlock_finish(): void
    {
        $this->fakeDatabase(fn () => throw new \RuntimeException('SQLSTATE[42000]: Access denied'));

        $this->withSession(['install.purchase_verified' => true])
            ->from(route('install.database'))
            ->post(route('install.database.store'), $this->dbForm())
            ->assertRedirect(route('install.database'))
            ->assertSessionHasErrors(['db_host' => 'Connected, but setting up the tables failed: SQLSTATE[42000]: Access denied Check that the database is empty and this user can create tables, then try again.'])
            ->assertSessionMissing('install.database_done');

        $this->get(route('install.finish'))->assertForbidden();
        $this->assertFileDoesNotExist($this->lock);
    }

    public function test_database_step_never_rotates_the_app_key(): void
    {
        $this->fakeDatabase();
        Artisan::shouldReceive('call')->with('key:generate', Mockery::any())->never();

        $this->withSession(['install.purchase_verified' => true])
            ->post(route('install.database.store'), $this->dbForm())
            ->assertRedirect(route('install.admin'))
            ->assertSessionHas('install.database_done', true);

        $env = File::get($this->dir.'/.env');
        $this->assertStringContainsString('DB_HOST=db.local', $env);
        $this->assertStringContainsString('DB_PASSWORD="p@ss $1 word"', $env);
    }

    public function test_happy_path_through_finish_writes_the_lock(): void
    {
        Http::fake(['license.test/*' => Http::response(['valid' => true, 'message' => 'ok', 'license' => 'Regular License'])]);
        $this->fakeDatabase();
        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            Artisan::shouldReceive('call')->once()->with($command);
        }

        $this->withSession(['install.requirements_ok' => true]);

        $this->post(route('install.purchase-code.verify'), ['purchase_code' => 'aaaaaaaa-1111-2222-3333-444444444444'])
            ->assertRedirect(route('install.database'));
        $this->get(route('install.database'))->assertOk();
        $this->post(route('install.database.store'), $this->dbForm())->assertRedirect(route('install.admin'));

        // The admin step itself (real DB) is covered by InstallerAdminStepTest.
        $this->get(route('install.finish'))->assertForbidden();
        $this->withSession(['install.admin_done' => true, 'install.admin_email' => 'owner@shop.test']);

        $this->get(route('install.finish'))
            ->assertOk()
            ->assertSee('owner@shop.test')
            ->assertDontSee('admin@example.com')
            ->assertSessionMissing('install.database_done')
            ->assertSessionMissing('install.purchase_verified');

        $this->assertFileExists($this->lock);
        $this->assertStringContainsString('APP_URL=http://localhost', File::get($this->dir.'/.env'));

        // Installed now: the wizard is gone.
        $this->get(route('install.finish'))->assertNotFound();
    }

    /** @return array<string, string> server vars for the project uploaded into /pos */
    private function inSubfolder(string $uri, bool $rewrite = true): array
    {
        [$server] = $rewrite
            ? [\App\Support\WebRoot::normalize(['SCRIPT_NAME' => '/pos/public/index.php', 'REQUEST_URI' => $uri])]
            : \App\Support\WebRoot::withoutRewrite(['SCRIPT_NAME' => '/pos/index.php', 'REQUEST_URI' => $uri]);

        return ['SCRIPT_NAME' => $server['SCRIPT_NAME'], 'PHP_SELF' => $server['SCRIPT_NAME'], 'SCRIPT_FILENAME' => base_path('index.php')];
    }

    public function test_finish_in_a_subfolder_writes_the_subfolder_app_url(): void
    {
        Artisan::shouldReceive('call')->times(3);

        $this->withServerVariables($this->inSubfolder('/pos/install/finish'))
            ->withSession(['install.database_done' => true, 'install.admin_done' => true])
            ->get('/pos/install/finish')
            ->assertOk();

        $this->assertStringContainsString("APP_URL=http://localhost/pos\n", File::get($this->dir.'/.env'));
    }

    public function test_finish_without_mod_rewrite_leaves_index_php_out_of_app_url(): void
    {
        Artisan::shouldReceive('call')->times(3);

        $this->withServerVariables($this->inSubfolder('/pos/index.php/install/finish', rewrite: false))
            ->withSession(['install.database_done' => true, 'install.admin_done' => true])
            ->get('/pos/index.php/install/finish')
            ->assertOk();

        $this->assertStringContainsString("APP_URL=http://localhost/pos\n", File::get($this->dir.'/.env'));
    }

    public function test_not_installed_app_redirects_visitors_to_the_installer(): void
    {
        config(['app.installer_redirect' => true]);

        $this->get('/')->assertRedirect(route('install.welcome'));
        $this->get('/login')->assertRedirect(route('install.welcome'));

        $this->get(route('install.welcome'))->assertOk();
        $this->get('/build/assets/missing.css')->assertNotFound();
    }

    public function test_redirect_can_be_switched_off(): void
    {
        config(['app.installer_redirect' => false]);

        $this->get('/login')->assertOk();
    }

    public function test_installed_app_hides_the_installer_and_does_not_redirect(): void
    {
        config(['app.installer_redirect' => true]);
        File::put($this->lock, 'installed');

        foreach (['install.welcome', 'install.requirements', 'install.purchase-code', 'install.database', 'install.admin', 'install.finish'] as $route) {
            $this->get(route($route))->assertNotFound();
        }

        $this->get('/login')->assertOk();
    }
}
