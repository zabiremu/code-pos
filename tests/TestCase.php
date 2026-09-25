<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // spatie/laravel-permission caches roles/permissions (config/permission.php's
        // 'cache' block). RefreshDatabase rolls back each test's DB transaction but
        // doesn't touch that cache, so role data from one test can leak into (or go
        // missing from) the next unless it's reset here.
        if ($this->app->bound(PermissionRegistrar::class)) {
            $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
