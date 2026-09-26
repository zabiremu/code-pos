<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'shop_name' => 'Rahim Store',
            'shop_phone' => '01711111111',
            'shop_email' => 'shop@example.com',
            'shop_address' => 'Mirpur 10, Dhaka',
            'currency' => 'bdt',
            'default_tax_rate' => '7.5',
            'timezone' => 'Asia/Dhaka',
            'receipt_footer' => 'Thank you!',
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.example.com',
            'mail_port' => '465',
            'mail_encryption' => 'ssl',
            'mail_username' => 'no-reply@example.com',
            'mail_password' => 'smtp-secret',
            'mail_from_address' => 'no-reply@example.com',
            'mail_from_name' => '',
        ], $overrides);
    }

    public function test_admin_can_open_settings(): void
    {
        $this->actingAs($this->staff('admin'))->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_managers_and_cashiers_cannot_open_settings(): void
    {
        $this->actingAs($this->staff('manager'))->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($this->staff('cashier'))->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_saving_updates_settings_branch_and_encrypts_the_smtp_password(): void
    {
        $this->actingAs($this->staff('admin'))
            ->put(route('admin.settings.update'), $this->payload())
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('Rahim Store', Setting::get('shop_name'));
        $this->assertSame('smtp-secret', Setting::get('mail_password'));
        $this->assertNotSame('smtp-secret', DB::table('settings')->where('key', 'mail_password')->value('value'));

        $branch = Branch::first();
        $this->assertSame('BDT', $branch->currency);
        $this->assertEquals(7.5, (float) $branch->tax_rate);
    }

    public function test_blank_password_keeps_the_saved_one(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin)->put(route('admin.settings.update'), $this->payload());
        $this->actingAs($admin)->put(route('admin.settings.update'), $this->payload(['mail_password' => '']));

        $this->assertSame('smtp-secret', Setting::get('mail_password'));
    }

    public function test_smtp_host_is_required_only_when_sending_with_smtp(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->payload(['mail_host' => '']))
            ->assertSessionHasErrors('mail_host');

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->payload(['mail_mailer' => 'log', 'mail_host' => '']))
            ->assertSessionHasNoErrors();
    }
}
