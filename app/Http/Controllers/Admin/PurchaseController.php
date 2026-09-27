<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchasingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Purchase orders. Receiving goods against one happens in GrnController. */
class PurchaseController extends Controller
{
    public function __construct(private PurchasingService $purchasing) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = array_key_exists((string) $request->query('status'), Purchase::STATUSES) ? $request->query('status') : 'all';

        $purchases = Purchase::with(['supplier:id,name,company_name', 'warehouse:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference_no', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($s) => $s->search($search))))
            ->latest('purchase_date')->latest('id')
            ->paginate(20)->withQueryString();

        $counts = Purchase::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.purchases.index', compact('purchases', 'search', 'status', 'counts'));
    }

    public function create(): View
    {
        return view('admin.purchases.form', $this->formData(new Purchase([
            'purchase_date' => today(),
            'status' => 'ordered',
            'warehouse_id' => Warehouse::default()->id,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $purchase = $this->purchasing->savePurchase($this->validated($request));

        return redirect()->route('admin.purchases.show', $purchase)->with('status', "Purchase {$purchase->reference_no} saved.");
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'warehouse', 'creator:id,name', 'items.product.unit', 'grns' => fn ($q) => $q->latest('received_date')]);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        if (! $purchase->isEditable()) {
            return redirect()->route('admin.purchases.show', $purchase)
                ->withErrors(['purchase' => 'Goods have already been received (or the order is cancelled), so this purchase can\'t be edited.']);
        }

        $purchase->load('items');

        return view('admin.purchases.form', $this->formData($purchase));
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        if (! $purchase->isEditable()) {
            return redirect()->route('admin.purchases.show', $purchase)->withErrors(['purchase' => 'This purchase can no longer be edited.']);
        }

        $this->purchasing->savePurchase($this->validated($request), $purchase);

        return redirect()->route('admin.purchases.show', $purchase)->with('status', "Purchase {$purchase->reference_no} updated.");
    }

    public function cancel(Purchase $purchase): RedirectResponse
    {
        if ($purchase->grns()->exists()) {
            return back()->withErrors(['purchase' => 'Goods have already been received against this purchase, so it can\'t be cancelled.']);
        }

        $purchase->update(['status' => 'cancelled']);

        return back()->with('status', "Purchase {$purchase->reference_no} cancelled.");
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        if ($purchase->grns()->exists()) {
            return back()->withErrors(['purchase' => 'Goods have been received against this purchase. Delete its GRNs first.']);
        }

        $purchase->delete();

        return redirect()->route('admin.purchases.index')->with('status', "Purchase {$purchase->reference_no} deleted.");
    }

    private function formData(Purchase $purchase): array
    {
        return [
            'purchase' => $purchase,
            'suppliers' => Supplier::where('is_active', true)->orWhere('id', $purchase->supplier_id)->orderBy('company_name')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orWhere('id', $purchase->warehouse_id)->orderByDesc('is_default')->orderBy('name')->get(),
            'productOptions' => self::productOptions(),
        ];
    }

    /** Products for the line-item picker: id, label, last cost, unit. */
    public static function productOptions(): array
    {
        return Product::with('unit:id,short_name,allow_decimal')->orderBy('name')
            ->get(['id', 'name', 'sku', 'purchase_price', 'unit_id'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'label' => $p->sku ? "{$p->name} ({$p->sku})" : $p->name,
                'cost' => (float) $p->purchase_price,
                'unit' => $p->unit?->short_name,
                'decimal' => (bool) ($p->unit?->allow_decimal ?? true),
            ])->all();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'status' => ['required', Rule::in(['draft', 'ordered'])],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'Choose a supplier.',
            'items.required' => 'Add at least one item.',
            'expected_date.after_or_equal' => 'The expected date can\'t be before the purchase date.',
        ]);
    }
}
