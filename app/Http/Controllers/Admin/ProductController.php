<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $filters = $request->only(['category_id', 'brand_id', 'stock']);

        $products = Product::with(['category:id,name', 'brand:id,name', 'unit:id,short_name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->where('track_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
            ->when(($filters['stock'] ?? null) === 'out', fn ($q) => $q->where('track_stock', true)->where('stock_quantity', '<=', 0))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'search' => $search,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product([
            'track_stock' => true,
            'is_available' => true,
            'low_stock_threshold' => 0,
        ])));
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $data = $this->validated($request);
        $opening = $request->validate([
            'opening_stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'opening_warehouse_id' => ['nullable', 'required_with:opening_stock', Rule::exists('warehouses', 'id')->where('is_active', true)],
        ]);

        $product = DB::transaction(function () use ($data, $opening, $stock) {
            $product = Product::create($data + ['stock_quantity' => 0]);

            $qty = (float) ($opening['opening_stock'] ?? 0);
            if ($qty > 0) {
                $stock->adjust($product, (int) $opening['opening_warehouse_id'], $qty, 'opening', 'Opening stock');
            }

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('status', "Product \"{$product->name}\" added.");
    }

    public function edit(Product $product): View
    {
        $product->load(['warehouseStocks.warehouse']);

        return view('admin.products.form', $this->formData($product) + [
            'movements' => $product->stockMovements()->with('warehouse:id,name')->latest()->take(10)->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('admin.products.edit', $product)->with('status', "Product \"{$product->name}\" updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $used = $product->saleItems()->exists()
            || PurchaseItem::where('product_id', $product->id)->exists()
            || GrnItem::where('product_id', $product->id)->exists();

        if ($used) {
            return back()->withErrors(['product' => "\"{$product->name}\" appears on sales or purchases, so it can't be deleted. Untick \"Available for sale\" to hide it instead."]);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', "Product \"{$product->name}\" deleted.");
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::where('is_active', true)->orWhere('id', $product->brand_id)->orderBy('name')->get(['id', 'name']),
            'units' => Unit::where('is_active', true)->orWhere('id', $product->unit_id)->orderBy('name')->get(['id', 'name', 'short_name']),
            'warehouses' => Warehouse::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'regular_price' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'gte:base_price'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ], [
            'sku.unique' => 'Another product already uses this SKU.',
            'base_price.required' => 'Enter the sale price.',
            'regular_price.gte' => 'The regular price should be the same as or higher than the sale price.',
        ]);

        return $data + [
            'purchase_price' => $data['purchase_price'] ?? 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 0,
            'track_stock' => $request->boolean('track_stock'),
            'is_available' => $request->boolean('is_available'),
        ];
    }
}
