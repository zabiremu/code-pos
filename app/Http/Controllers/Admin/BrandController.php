<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $brands = Brand::withCount('products')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('is_active')->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('admin.brands.index', compact('brands', 'search'));
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $brand = Brand::create($this->validated($request));

        return redirect()->route('admin.brands.index')->with('status', "Brand \"{$brand->name}\" added.");
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->validated($request, $brand));

        return redirect()->route('admin.brands.index')->with('status', "Brand \"{$brand->name}\" updated.");
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $count = $brand->products()->count();
        $brand->delete(); // products.brand_id is nullOnDelete

        return redirect()->route('admin.brands.index')->with('status', "Brand \"{$brand->name}\" deleted."
            .($count ? " {$count} product(s) no longer have a brand." : ''));
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('brands', 'name')->ignore($brand)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], ['name.unique' => 'A brand with this name already exists.']) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
