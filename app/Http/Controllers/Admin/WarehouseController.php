<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\Purchase;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::withCount(['stocks as products_in_stock' => fn ($q) => $q->where('quantity', '>', 0)])
            ->withSum('stocks as total_units', 'quantity')
            ->orderByDesc('is_default')->orderByDesc('is_active')->orderBy('name')
            ->get();

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('admin.warehouses.form', ['warehouse' => new Warehouse(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $warehouse = DB::transaction(fn () => $this->save(new Warehouse, $this->validated($request)));

        return redirect()->route('admin.warehouses.index')->with('status', "Warehouse \"{$warehouse->name}\" added.");
    }

    public function edit(Warehouse $warehouse): View
    {
        $warehouse->load(['stocks' => fn ($q) => $q->where('quantity', '!=', 0)->with('product:id,name,sku')->orderByDesc('quantity')]);

        return view('admin.warehouses.form', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $this->validated($request, $warehouse);

        if ($warehouse->is_default && ! $data['is_default']) {
            return back()->withInput()->withErrors(['is_default' => 'Make another warehouse the default first - sales need one to take stock from.']);
        }
        if ($warehouse->is_default && ! $data['is_active']) {
            return back()->withInput()->withErrors(['is_active' => 'The default warehouse can\'t be inactive.']);
        }

        DB::transaction(fn () => $this->save($warehouse, $data));

        return redirect()->route('admin.warehouses.index')->with('status', "Warehouse \"{$warehouse->name}\" updated.");
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->is_default) {
            return back()->withErrors(['warehouse' => 'The default warehouse can\'t be deleted. Make another one the default first.']);
        }
        if ($warehouse->stocks()->where('quantity', '!=', 0)->exists()) {
            return back()->withErrors(['warehouse' => "\"{$warehouse->name}\" still holds stock. Move or return it before deleting, or mark the warehouse inactive."]);
        }
        if (Purchase::where('warehouse_id', $warehouse->id)->exists() || Grn::where('warehouse_id', $warehouse->id)->exists()) {
            return back()->withErrors(['warehouse' => "\"{$warehouse->name}\" is used on purchases or GRNs, so it can't be deleted. Mark it inactive instead."]);
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')->with('status', "Warehouse \"{$warehouse->name}\" deleted.");
    }

    private function save(Warehouse $warehouse, array $data): Warehouse
    {
        if ($data['is_default']) {
            Warehouse::where('is_default', true)->where('id', '!=', $warehouse->id ?? 0)->update(['is_default' => false]);
            $data['is_active'] = true;
        }
        $warehouse->fill($data)->save();

        return $warehouse;
    }

    private function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('warehouses', 'code')->ignore($warehouse)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ], ['code.unique' => 'Another warehouse already uses this code.']) + [
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
