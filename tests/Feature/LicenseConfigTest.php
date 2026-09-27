<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A blank `LICENSE_SERVER_URL=` line in .env used to resolve to '' (env()'s
 * default only applies when the key is absent), so the installer posted to
 * nowhere and always said "Could not reach the license server".
 */
class LicenseConfigTest extends TestCase
{
    private const DEFAULT = 'https://license.digitalstorebd.fun';

    private array $saved;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saved = [getenv('LICENSE_SERVER_URL'), $_ENV['LICENSE_SERVER_URL'] ?? null, $_SERVER['LICENSE_SERVER_URL'] ?? null];
    }

    protected function tearDown(): void
    {
        [$putenv, $env, $server] = $this->saved;
        $putenv === false ? putenv('LICENSE_SERVER_URL') : putenv('LICENSE_SERVER_URL='.$putenv);
        if ($env === null) {
            unset($_ENV['LICENSE_SERVER_URL']);
        } else {
            $_ENV['LICENSE_SERVER_URL'] = $env;
        }
        if ($server === null) {
            unset($_SERVER['LICENSE_SERVER_URL']);
        } else {
            $_SERVER['LICENSE_SERVER_URL'] = $server;
        }
        parent::tearDown();
    }

    private function serverFor(string $value): string
    {
        putenv('LICENSE_SERVER_URL='.$value);
        $_ENV['LICENSE_SERVER_URL'] = $value;
        $_SERVER['LICENSE_SERVER_URL'] = $value;

        return (require config_path('license.php'))['server'];
    }

    public function test_a_blank_license_server_url_falls_back_to_the_default(): void
    {
        $this->assertSame(self::DEFAULT, $this->serverFor(''));
    }

    public function test_a_set_license_server_url_is_used(): void
    {
        $this->assertSame('https://licenses.example.com', $this->serverFor('https://licenses.example.com'));
    }
}
