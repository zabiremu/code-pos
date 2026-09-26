<?php

namespace Tests\Feature;

use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * Self-service clock-in/clock-out (App\Http\Controllers\ShiftController),
 * open to any authenticated role - not the admin|manager attendance report,
 * which is covered separately in Tests\Feature\Admin\ShiftReportTest.
 */
class ShiftTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_a_user_can_clock_in(): void
    {
        $user = $this->staff('cashier');

        $this->actingAs($user)
            ->post(route('shifts.clock-in'), ['opening_till' => 100])
            ->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'user_id' => $user->id,
            'opening_till' => 100,
            'clock_out' => null,
        ]);
        $this->assertNotNull($user->fresh()->activeShift());
    }

    public function test_a_user_cannot_clock_in_twice(): void
    {
        $user = $this->staff('cashier');
        $user->shifts()->create(['clock_in' => now(), 'opening_till' => 50]);

        $this->actingAs($user)
            ->post(route('shifts.clock-in'), ['opening_till' => 75])
            ->assertSessionHasErrors('shift');

        $this->assertSame(1, $user->shifts()->count());
    }

    public function test_a_user_can_clock_out_of_their_active_shift(): void
    {
        $user = $this->staff('cashier');
        $shift = $user->shifts()->create(['clock_in' => now(), 'opening_till' => 50]);

        $this->actingAs($user)
            ->post(route('shifts.clock-out'), ['closing_till' => 120])
            ->assertRedirect();

        $shift->refresh();
        $this->assertNotNull($shift->clock_out);
        $this->assertEquals(120, $shift->closing_till);
        $this->assertNull($user->fresh()->activeShift());
    }

    public function test_a_user_cannot_clock_out_when_not_clocked_in(): void
    {
        $user = $this->staff('cashier');

        $this->actingAs($user)
            ->post(route('shifts.clock-out'), ['closing_till' => 100])
            ->assertSessionHasErrors('shift');

        $this->assertSame(0, Shift::count());
    }

    public function test_guests_cannot_clock_in_or_out(): void
    {
        $this->post(route('shifts.clock-in'), ['opening_till' => 0])->assertRedirect('/login');
        $this->post(route('shifts.clock-out'), ['closing_till' => 0])->assertRedirect('/login');
    }
}
