<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Self-service shift clock-in/clock-out, open to any authenticated role
 * (part of Employee Management, alongside Admin\ShiftController's
 * attendance report). Uses the `shifts` table that was already in the
 * original schema (opening_till/closing_till for cash-drawer reconciliation)
 * but had no controller/route/view wired up to it at all until now.
 */
class ShiftController extends Controller
{
    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->activeShift()) {
            return back()->withErrors(['shift' => 'You are already clocked in.']);
        }

        $data = $request->validate([
            'opening_till' => ['required', 'numeric', 'min:0'],
        ]);

        $user->shifts()->create([
            'clock_in' => now(),
            'opening_till' => $data['opening_till'],
        ]);

        return back()->with('status', 'Clocked in - have a good shift!');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shift = $user->activeShift();

        if (! $shift) {
            return back()->withErrors(['shift' => 'You are not currently clocked in.']);
        }

        $data = $request->validate([
            'closing_till' => ['required', 'numeric', 'min:0'],
        ]);

        $shift->update([
            'clock_out' => now(),
            'closing_till' => $data['closing_till'],
        ]);

        return back()->with('status', 'Clocked out - see you next shift!');
    }
}
