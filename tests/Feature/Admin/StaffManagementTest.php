<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_admin_can_view_an_employees_full_profile(): void
    {
        $employee = $this->staff('waiter', ['name' => 'Rana']);

        $this->actingAs($this->staff('admin'))
            ->get(route('admin.staff.show', $employee))
            ->assertOk()
            ->assertSee('Rana');
    }

    public function test_a_non_manager_cannot_view_the_employee_profile_page(): void
    {
        $employee = $this->staff('waiter');

        $this->actingAs($this->staff('cashier'))
            ->get(route('admin.staff.show', $employee))
            ->assertForbidden();
    }

    public function test_admin_can_update_an_employees_full_profile(): void
    {
        $employee = $this->staff('waiter', ['name' => 'Old Name']);

        $this->actingAs($this->staff('admin'))
            ->put(route('admin.staff.update', $employee), [
                'name' => 'New Name',
                'email' => $employee->email,
                'phone' => '01711111111',
                'branch_id' => '',
                'role' => 'cashier',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $employee->refresh();
        $this->assertSame('New Name', $employee->name);
        $this->assertSame('01711111111', $employee->phone);
        $this->assertTrue($employee->hasRole('cashier'));
        $this->assertFalse($employee->hasRole('waiter'));
    }

    public function test_updating_an_employee_can_deactivate_their_account(): void
    {
        $employee = $this->staff('waiter');

        $this->actingAs($this->staff('admin'))
            ->put(route('admin.staff.update', $employee), [
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => 'waiter',
                // is_active deliberately omitted, like an unchecked checkbox -
                // the form's hidden "0" fallback input is what makes this work.
            ]);

        $this->assertFalse($employee->fresh()->is_active);
    }

    public function test_employee_update_rejects_an_email_already_used_by_someone_else(): void
    {
        $employee = $this->staff('waiter');
        $other = $this->staff('cashier');

        $this->actingAs($this->staff('admin'))
            ->put(route('admin.staff.update', $employee), [
                'name' => $employee->name,
                'email' => $other->email,
                'role' => 'waiter',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_employee_update_allows_keeping_their_own_email(): void
    {
        $employee = $this->staff('waiter');

        $this->actingAs($this->staff('admin'))
            ->put(route('admin.staff.update', $employee), [
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => 'waiter',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_new_staff_can_be_created_with_a_phone_number(): void
    {
        $this->actingAs($this->staff('admin'))
            ->post(route('admin.staff.store'), [
                'name' => 'New Hire',
                'email' => 'new-hire@example.com',
                'phone' => '01799999999',
                'role' => 'cashier',
                'password' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'new-hire@example.com',
            'phone' => '01799999999',
        ]);
    }
}
