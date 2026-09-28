<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * demo:reset wipes the database, so it must refuse to run unless DEMO_MODE
 * is on. DatabaseMigrations (not RefreshDatabase): migrate:fresh can't run
 * inside RefreshDatabase's wrapping transaction.
 */
class DemoResetCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function test_refuses_to_run_outside_demo_mode_and_touches_nothing(): void
    {
        config(['app.demo_mode' => false]);
        $supplier = Supplier::create(['name' => 'Real Supplier']);

        $this->artisan('demo:reset')
            ->expectsOutputToContain('Refusing to run')
            ->assertFailed();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_in_demo_mode_it_wipes_visitor_changes_and_restores_the_demo_logins(): void
    {
        config(['app.demo_mode' => true]);
        Supplier::create(['name' => 'Added by a visitor']);

        $uploads = public_path('uploads');
        $created = ! File::isDirectory($uploads);
        File::ensureDirectoryExists($uploads);
        $visitorPhoto = $uploads.'/demo-reset-test-'.uniqid().'.webp';
        File::put($visitorPhoto, 'x');

        try {
            $this->artisan('demo:reset')->assertSuccessful();

            $this->assertDatabaseMissing('suppliers', ['name' => 'Added by a visitor']);
            $this->assertFileDoesNotExist($visitorPhoto);

            foreach (['admin@example.com' => 'admin', 'manager@example.com' => 'manager', 'cashier@example.com' => 'cashier'] as $email => $role) {
                $user = User::where('email', $email)->firstOrFail();
                $this->assertTrue(Hash::check('password', $user->password));
                $this->assertTrue($user->hasRole($role));
            }
        } finally {
            File::delete($visitorPhoto);
            if ($created) {
                File::deleteDirectory($uploads);
            }
        }
    }
}
