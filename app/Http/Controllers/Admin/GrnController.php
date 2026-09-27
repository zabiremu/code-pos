<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchasingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Goods Received Notes - the moment stock actually arrives. */
class GrnController extends Controller
{
    public function __construct(private PurchasingService $purchasing) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $grns = Grn::with(['supplier:id,name,company_name', 'warehouse:id,name', 'purchase:id,reference_no'])
            ->withCount('returns')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('grn_no', 'like', "%{$search}%")
                ->orWhere('supplier_invoice_no', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($s) => $s->search($search))))
            ->latest('received_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.grns.index', compact('grns', 'search'));
    }

    /** ?purchase_id=… pre-fills the lines still to be received on that purchase. */
    public function create(Request $request): View|RedirectResponse
    {
        $purchase = $request->filled('purchase_id') ? Purchase::with('items.product')->findOrFail($request->integer('purchase_id')) : null;

        if ($purchase && ! $purchase->canReceive()) {
            return redirect()->route('admin.purchases.show', $purchase)->withErrors(['purchase' => 'Everything on this purchase has been received (or it was cancelled).']);
        }

        $grn = new Grn([
            'received_date' => today(),
            'purchase_id' => $purchase?->id,
            'supplier_id' => $purchase?->supplier_id,
            'warehouse_id' => $purchase?->warehouse_id ?? Warehouse::default()->id,
        ]);

        $lines = $purchase
            ? $purchase->items->filter(fn ($i) => $i->remainingQuantity() > 0)->map(fn ($i) => [
                'product_id' => $i->product_id,
                'purchase_item_id' => $i->id,
                'quantity' => $i->remainingQuantity(),
                'unit_cost' => (float) $i->unit_cost,
                'ordered' => $i->remainingQuantity(),
            ])->values()->all()
            : [];

        return view('admin.grns.form', $this->formData($grn, $purchase, $lines));
    }

    public function store(Request $request): RedirectResponse
    {
        $grn = $this->purchasing->saveGrn($this->validated($request));

        return redirect()->route('admin.grns.show', $grn)->with('status', "{$grn->grn_no} saved. Stock added to {$grn->warehouse->name}.");
    }

    public function show(Grn $grn): View
    {
        $grn->load(['supplier', 'warehouse', 'purchase', 'creator:id,name', 'items.product.unit', 'returns' => fn ($q) => $q->latest('return_date')]);

        return view('admin.grns.show', compact('grn'));
    }

    public function edit(Grn $grn): View|RedirectResponse
    {
        if (! $grn->isEditable()) {
            return redirect()->route('admin.grns.show', $grn)->withErrors(['grn' => 'Goods have been returned from this GRN, so it can no longer be edited.']);
        }

        $grn->load(['items', 'purchase.items']);
        $remaining = $grn->purchase?->items->mapWithKeys(fn ($i) => [$i->id => $i->remainingQuantity()]) ?? collect();

        $lines = $grn->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'purchase_item_id' => $i->purchase_item_id,
            'quantity' => (float) $i->quantity,
            'unit_cost' => (float) $i->unit_cost,
            // What's still open on the PO, counting this GRN's own quantity back in.
            'ordered' => $i->purchase_item_id ? ($remaining[$i->purchase_item_id] ?? 0) + (float) $i->quantity : null,
        ])->all();

        return view('admin.grns.form', $this->formData($grn, $grn->purchase, $lines));
    }

    public function update(Request $request, Grn $grn): RedirectResponse
    {
        $this->purchasing->saveGrn($this->validated($request), $grn);

        return redirect()->route('admin.grns.show', $grn)->with('status', "{$grn->grn_no} updated.");
    }

    public function destroy(Grn $grn): RedirectResponse
    {
        $this->purchasing->deleteGrn($grn);

        return redirect()->route('admin.grns.index')->with('status', "{$grn->grn_no} deleted and its stock removed.");
    }

    private function formData(Grn $grn, ?Purchase $purchase, array $lines): array
    {
        return [
            'grn' => $grn,
            'purchase' => $purchase,
            'lines' => $lines,
            'suppliers' => Supplier::where('is_active', true)->orWhere('id', $grn->supplier_id)->orderBy('company_name')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orWhere('id', $grn->warehouse_id)->orderByDesc('is_default')->orderBy('name')->get(),
            'productOptions' => PurchaseController::productOptions(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'received_date' => ['required', 'date', 'before_or_equal:today'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.purchase_item_id' => ['nullable', 'exists:purchase_items,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => 'Choose a supplier.',
            'received_date.before_or_equal' => 'The received date can\'t be in the future.',
        ]);
    }
}
