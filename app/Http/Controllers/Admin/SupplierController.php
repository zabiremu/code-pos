<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin > Suppliers: the people and companies the shop buys stock from. */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : 'all';

        $suppliers = Supplier::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status !== 'all', fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('company_name')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Supplier::count(),
            'active' => Supplier::where('is_active', true)->count(),
            'inactive' => Supplier::where('is_active', false)->count(),
        ];

        return view('admin.suppliers.index', compact('suppliers', 'search', 'status', 'counts'));
    }

    public function create(): View
    {
        return view('admin.suppliers.create', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('admin.suppliers.index')
            ->with('status', 'Supplier "'.$supplier->displayName().'" added.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('admin.suppliers.index')
            ->with('status', 'Supplier "'.$supplier->displayName().'" updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $name = $supplier->displayName();
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier "'.$name.'" deleted.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('suppliers', 'email')->ignore($supplier)],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.unique' => 'Another supplier already uses this email.',
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
