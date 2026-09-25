<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Floor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index(): View
    {
        $floors = Floor::with('tables')->orderBy('sort_order')->get();

        return view('admin.floors.index', compact('floors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $branch = Branch::firstOrFail();

        Floor::create($data + ['branch_id' => $branch->id]);

        return back()->with('status', 'Floor created.');
    }

    public function destroy(Floor $floor): RedirectResponse
    {
        $floor->delete();

        return back()->with('status', 'Floor deleted.');
    }
}
