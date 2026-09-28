<?php

namespace Tests\Feature;

use App\Enums\Role as PosRole;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\InstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The installer's admin step: the buyer creates their own login, and a real
 * install never contains the publicly known demo account. Temp .env/lock
 * paths only - never the real ones.
 */
class InstallerAdminStepTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/installer-admin-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        File::put($this->dir.'/.env', "APP_NAME=Test\nAPP_URL=http://localhost\n");
        $this->app->useEnvironmentPath($this->dir);
        config(['app.installed_lock' => $this->dir.'/installed.lock']);

        // What the database step leaves behind: roles + branch, no users.
        $this->seed(InstallSeeder::class);
        $this->withSession(['install.database_done' => true]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Shop Owner',
            'email' => 'owner@shop.test',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
        ], $overrides);
    }

    public function test_install_seeder_creates_roles_and_branch_but_no_users(): void
    {
        $this->assertSame(0, User::count());
        foreach (PosRole::values() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'guard_name' => 'web']);
        }
        $this->assertDatabaseHas('branches', ['name' => 'Main Branch']);
    }

    public function test_admin_page_renders(): void
    {
        $this->get(route('install.admin'))->assertOk()->assertSee('Create your admin account');
    }

    public function test_buyer_creates_their_own_admin_and_no_demo_login_exists(): void
    {
        $this->post(route('install.admin.store'), $this->form())
            ->assertRedirect(route('install.finish'))
            ->assertSessionHas('install.admin_done', true);

        $admin = User::where('email', 'owner@shop.test')->firstOrFail();
        $this->assertTrue($admin->hasRole(PosRole::Admin->value));
        $this->assertTrue($admin->is_active);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertNotNull($admin->branch_id);
        $this->assertTrue(Hash::check('correct-horse', $admin->password));

        $this->assertSame(1, User::count());
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertSame(0, Product::count());
    }

    public function test_the_new_admin_can_log_in(): void
    {
        $this->post(route('install.admin.store'), $this->form());

        $this->post(route('login'), ['email' => 'owner@shop.test', 'password' => 'correct-horse']);
        $this->assertAuthenticatedAs(User::where('email', 'owner@shop.test')->first());
    }

    public function test_validation(): void
    {
        $this->post(route('install.admin.store'), $this->form(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');
        $this->post(route('install.admin.store'), $this->form(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');
        $this->post(route('install.admin.store'), $this->form(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
        $this->post(route('install.admin.store'), $this->form(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, User::count());
        $this->assertFalse(session()->has('install.admin_done'));
    }

    public function test_a_second_submit_does_not_create_a_second_admin(): void
    {
        $this->post(route('install.admin.store'), $this->form())->assertRedirect(route('install.finish'));
        $this->post(route('install.admin.store'), $this->form(['email' => 'other@shop.test']))->assertRedirect(route('install.finish'));
        $this->get(route('install.admin'))->assertRedirect(route('install.finish'));

        $this->assertSame(1, User::count());
    }

    public function test_sample_data_is_only_added_when_asked(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', ['--class' => DemoProductSeeder::class, '--force' => true])
            ->andReturn(0);

        $this->post(route('install.admin.store'), $this->form(['sample_data' => '1']))
            ->assertRedirect(route('install.finish'));
    }

    public function test_finish_shows_the_buyers_email_not_a_demo_password(): void
    {
        $this->post(route('install.admin.store'), $this->form());

        Artisan::shouldReceive('call')->times(3);

        $this->get(route('install.finish'))
            ->assertOk()
            ->assertSee('owner@shop.test')
            ->assertDontSee('admin@example.com')
            ->assertDontSee('/ password');

        $this->assertFileExists($this->dir.'/installed.lock');
    }
}
