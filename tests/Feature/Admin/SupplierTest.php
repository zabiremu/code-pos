<?php

namespace Tests\Feature\Admin;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_manager_can_list_and_search_suppliers(): void
    {
        Supplier::factory()->create(['company_name' => 'Karim Traders']);
        Supplier::factory()->create(['company_name' => 'Dhaka Wholesale']);

        $this->actingAs($this->staff('manager'))
            ->get(route('admin.suppliers.index', ['q' => 'Karim']))
            ->assertOk()
            ->assertSee('Karim Traders')
            ->assertDontSee('Dhaka Wholesale');
    }

    public function test_cashier_cannot_manage_suppliers(): void
    {
        $this->actingAs($this->staff('cashier'))
            ->get(route('admin.suppliers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_supplier(): void
    {
        $this->actingAs($this->staff('admin'))
            ->post(route('admin.suppliers.store'), [
                'name' => 'Rahim Uddin',
                'company_name' => 'Rahim Enterprise',
                'phone' => '01711111111',
                'email' => 'rahim@example.com',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['company_name' => 'Rahim Enterprise', 'is_active' => true]);
    }

    public function test_name_is_required_and_email_must_be_unique(): void
    {
        Supplier::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->staff('admin'))
            ->post(route('admin.suppliers.store'), ['name' => '', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_admin_can_update_and_deactivate_a_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->staff('admin'))
            ->put(route('admin.suppliers.update', $supplier), [
                'name' => 'New Contact',
                'email' => $supplier->email, // keeping its own email isn't a duplicate
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.suppliers.index'))
            ->assertSessionHasNoErrors();

        $supplier->refresh();
        $this->assertSame('New Contact', $supplier->name);
        $this->assertFalse($supplier->is_active);
    }

    public function test_admin_can_delete_a_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->staff('admin'))
            ->delete(route('admin.suppliers.destroy', $supplier))
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertModelMissing($supplier);
    }
}
