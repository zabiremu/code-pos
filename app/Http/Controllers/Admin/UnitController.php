<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $units = Unit::withCount('products')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")))
            ->orderByDesc('is_active')->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('admin.units.index', compact('units', 'search'));
    }

    public function create(): View
    {
        return view('admin.units.form', ['unit' => new Unit(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = Unit::create($this->validated($request));

        return redirect()->route('admin.units.index')->with('status', "Unit \"{$unit->name}\" added.");
    }

    public function edit(Unit $unit): View
    {
        return view('admin.units.form', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($this->validated($request));

        return redirect()->route('admin.units.index')->with('status', "Unit \"{$unit->name}\" updated.");
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $count = $unit->products()->count();
        $unit->delete(); // products.unit_id is nullOnDelete

        return redirect()->route('admin.units.index')->with('status', "Unit \"{$unit->name}\" deleted."
            .($count ? " {$count} product(s) no longer have a unit." : ''));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['required', 'string', 'max:20'],
        ]) + [
            'allow_decimal' => $request->boolean('allow_decimal'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
