<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index(): View
    {
        $items = MenuItem::with('category')->latest()->paginate(20);
        $categories = Category::orderBy('name')->get();

        return view('admin.menu-items.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $modifierGroups = ModifierGroup::orderBy('name')->get();

        return view('admin.menu-items.create', compact('categories', 'modifierGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $item = MenuItem::create($data);

        $this->syncVariants($request, $item);
        $item->modifierGroups()->sync($request->input('modifier_groups', []));

        return redirect()->route('admin.menu-items.index')->with('status', 'Menu item created.');
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $this->validated($request);

        $menuItem->update($data);

        $this->syncVariants($request, $menuItem);
        $menuItem->modifierGroups()->sync($request->input('modifier_groups', []));

        return back()->with('status', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return back()->with('status', 'Menu item deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_available' => ['boolean'],
        ]);
    }

    /** Replace an item's variants from the request's `variants[]` array (each: name, price_delta). */
    private function syncVariants(Request $request, MenuItem $item): void
    {
        if (! $request->has('variants')) {
            return;
        }

        $item->variants()->delete();

        foreach ($request->input('variants', []) as $index => $variant) {
            if (empty($variant['name'])) {
                continue;
            }

            $item->variants()->create([
                'name' => $variant['name'],
                'price_delta' => $variant['price_delta'] ?? 0,
                'is_default' => $index == 0,
            ]);
        }
    }
}
