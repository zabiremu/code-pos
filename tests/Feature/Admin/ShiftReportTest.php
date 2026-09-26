<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class ShiftReportTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_admin_can_view_the_attendance_report(): void
    {
        $employee = $this->staff('cashier', ['name' => 'Rana']);
        $employee->shifts()->create(['clock_in' => now()->subHours(2), 'clock_out' => now(), 'opening_till' => 50, 'closing_till' => 200]);

        $this->actingAs($this->staff('admin'))
            ->get(route('admin.shifts.index'))
            ->assertOk()
            ->assertSee('Rana');
    }

    public function test_manager_can_also_view_the_attendance_report(): void
    {
        $this->actingAs($this->staff('manager'))
            ->get(route('admin.shifts.index'))
            ->assertOk();
    }

    public function test_cashier_is_forbidden_from_the_attendance_report(): void
    {
        $this->actingAs($this->staff('cashier'))
            ->get(route('admin.shifts.index'))
            ->assertForbidden();
    }

    public function test_the_report_can_be_filtered_to_a_single_employee(): void
    {
        // assertViewHas rather than assertSee/assertDontSee: the filter
        // dropdown itself lists every employee's name, so a name being
        // present in the rendered HTML doesn't mean it's in the results.
        $rana = $this->staff('cashier', ['name' => 'Rana']);
        $rana->shifts()->create(['clock_in' => now(), 'opening_till' => 50]);

        $karim = $this->staff('cashier', ['name' => 'Karim']);
        $karim->shifts()->create(['clock_in' => now(), 'opening_till' => 30]);

        $response = $this->actingAs($this->staff('admin'))
            ->get(route('admin.shifts.index', ['user_id' => $rana->id]));

        $response->assertOk();
        $response->assertViewHas('shifts', function ($shifts) use ($rana) {
            return $shifts->count() === 1 && $shifts->first()->user_id === $rana->id;
        });
    }

    public function test_the_report_can_be_filtered_to_only_currently_open_shifts(): void
    {
        $rana = $this->staff('cashier', ['name' => 'Rana']);
        $rana->shifts()->create(['clock_in' => now()->subHour(), 'clock_out' => now(), 'opening_till' => 50, 'closing_till' => 60]);

        $karim = $this->staff('cashier', ['name' => 'Karim']);
        $karim->shifts()->create(['clock_in' => now(), 'opening_till' => 30]);

        $response = $this->actingAs($this->staff('admin'))
            ->get(route('admin.shifts.index', ['open' => 1]));

        $response->assertOk();
        $response->assertViewHas('shifts', function ($shifts) use ($karim) {
            return $shifts->count() === 1 && $shifts->first()->user_id === $karim->id;
        });
    }
}
