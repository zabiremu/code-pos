<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModifierGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModifierGroupController extends Controller
{
    public function index(): View
    {
        $groups = ModifierGroup::with('modifiers')->orderBy('name')->paginate(20);

        return view('admin.modifier-groups.index', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_required' => ['boolean'],
            'max_selectable' => ['required', 'integer', 'min:1'],
            'modifiers' => ['array'],
            'modifiers.*.name' => ['required_with:modifiers', 'string', 'max:100'],
            'modifiers.*.price_delta' => ['nullable', 'numeric'],
        ]);

        $group = ModifierGroup::create([
            'name' => $data['name'],
            'is_required' => $data['is_required'] ?? false,
            'max_selectable' => $data['max_selectable'],
        ]);

        foreach ($data['modifiers'] ?? [] as $modifier) {
            $group->modifiers()->create([
                'name' => $modifier['name'],
                'price_delta' => $modifier['price_delta'] ?? 0,
            ]);
        }

        return back()->with('status', 'Modifier group created.');
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $modifierGroup->delete();

        return back()->with('status', 'Modifier group deleted.');
    }
}
