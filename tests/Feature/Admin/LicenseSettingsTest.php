<?php

namespace Tests\Feature\Admin;

use App\Services\PurchaseCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class LicenseSettingsTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private const CODE = 'aaaaaaaa-1111-2222-3333-444444444444';

    protected function setUp(): void
    {
        parent::setUp();
        config(['license.server' => 'https://license.test', 'license.file' => storage_path('framework/testing/license.json')]);
        app(PurchaseCodeService::class)->store(self::CODE, 'localhost', ['license' => 'Regular License']);
    }

    protected function tearDown(): void
    {
        File::delete(config('license.file'));
        parent::tearDown();
    }

    public function test_settings_page_shows_masked_license(): void
    {
        $this->actingAs($this->staff('admin'))
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('444444444444')
            ->assertDontSee(self::CODE);
    }

    public function test_admin_can_deactivate_license(): void
    {
        Http::fake(['license.test/*' => Http::response(['valid' => true, 'message' => 'License deactivated.'])]);

        $this->actingAs($this->staff('admin'))
            ->post(route('admin.settings.license.deactivate'))
            ->assertSessionHas('status');

        Http::assertSent(fn ($r) => $r['action'] === 'deactivate' && $r['purchase_code'] === self::CODE);
        $this->assertNull(app(PurchaseCodeService::class)->current());
    }

    public function test_failed_deactivation_keeps_the_local_license(): void
    {
        Http::fake(['license.test/*' => Http::response(['valid' => false, 'message' => 'Nope.'], 403)]);

        $this->actingAs($this->staff('admin'))
            ->post(route('admin.settings.license.deactivate'))
            ->assertSessionHasErrors(['license' => 'Nope.']);

        $this->assertNotNull(app(PurchaseCodeService::class)->current());
    }

    public function test_manager_cannot_deactivate(): void
    {
        Http::fake();

        $this->actingAs($this->staff('manager'))
            ->post(route('admin.settings.license.deactivate'))
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
