<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Attendance report across every employee's shifts - admin|manager only.
 * The self-service clock-in/out itself lives in the top-level
 * App\Http\Controllers\ShiftController, open to any authenticated role.
 */
class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->query('user_id', '');
        $onlyOpen = $request->boolean('open');

        $shifts = Shift::with('user')
            ->when($userId !== '', fn ($query) => $query->where('user_id', $userId))
            ->when($onlyOpen, fn ($query) => $query->whereNull('clock_out'))
            ->latest('clock_in')
            ->paginate(20)
            ->withQueryString();

        return view('admin.shifts.index', [
            'shifts' => $shifts,
            'employees' => User::orderBy('name')->get(['id', 'name']),
            'userId' => $userId,
            'onlyOpen' => $onlyOpen,
            'openCount' => Shift::whereNull('clock_out')->count(),
        ]);
    }
}
