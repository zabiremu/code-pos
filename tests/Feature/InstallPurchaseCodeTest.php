<?php

namespace Tests\Feature;

use App\Services\PurchaseCodeService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstallPurchaseCodeTest extends TestCase
{
    private const CODE = 'aaaaaaaa-1111-2222-3333-444444444444';

    protected function setUp(): void
    {
        parent::setUp();
        config(['license.server' => 'https://license.test', 'license.file' => storage_path('framework/testing/license.json')]);
        File::delete(config('license.file'));

        // Never the real storage/installed.lock: point at a path that doesn't exist so /install is open.
        config(['app.installed_lock' => storage_path('framework/testing/installed-'.uniqid().'.lock')]);
        // The purchase-code step is gated on the requirements step having passed.
        $this->withSession(['install.requirements_ok' => true]);
    }

    protected function tearDown(): void
    {
        File::delete(config('license.file'));
        parent::tearDown();
    }

    public function test_valid_code_is_verified_without_any_envato_token(): void
    {
        Http::fake(['license.test/*' => Http::response(['valid' => true, 'message' => 'ok', 'license' => 'Regular License', 'supported_until' => '2027-01-01T00:00:00+00:00'])]);

        $this->post(route('install.purchase-code.verify'), ['purchase_code' => strtoupper(self::CODE)])
            ->assertRedirect(route('install.database'))
            ->assertSessionHas('install.purchase_verified', true);

        Http::assertSent(fn ($request) => $request['action'] === 'activate'
            && $request['purchase_code'] === self::CODE
            && $request['domain'] !== ''
            && ! isset($request['envato_token']));

        $license = app(PurchaseCodeService::class)->current();
        $this->assertSame(self::CODE, $license['purchase_code']);
        $this->assertSame('Regular License', $license['license']);
    }

    public function test_server_rejection_message_is_shown(): void
    {
        Http::fake(['license.test/*' => Http::response(['valid' => false, 'message' => 'This purchase code is already activated on shop.com.'], 409)]);

        $this->from(route('install.purchase-code'))
            ->post(route('install.purchase-code.verify'), ['purchase_code' => self::CODE])
            ->assertRedirect(route('install.purchase-code'))
            ->assertSessionHasErrors(['purchase_code' => 'This purchase code is already activated on shop.com.'])
            ->assertSessionMissing('install.purchase_verified');

        $this->assertNull(app(PurchaseCodeService::class)->current());
    }

    public function test_badly_formatted_code_never_reaches_the_server(): void
    {
        Http::fake();

        $this->post(route('install.purchase-code.verify'), ['purchase_code' => 'not-a-code'])
            ->assertSessionHasErrors('purchase_code');

        Http::assertNothingSent();
    }

    public function test_unreachable_server_gives_a_friendly_error(): void
    {
        Http::fake(['license.test/*' => Http::failedConnection()]);

        $result = app(PurchaseCodeService::class)->verify(self::CODE, 'shop.com');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Could not reach the license server', $result['message']);
    }

    public function test_an_error_status_is_never_treated_as_valid(): void
    {
        Http::fake(['license.test/*' => Http::response(['valid' => true, 'message' => 'odd'], 500)]);

        $this->assertFalse(app(PurchaseCodeService::class)->verify(self::CODE, 'shop.com')['valid']);
    }
}
