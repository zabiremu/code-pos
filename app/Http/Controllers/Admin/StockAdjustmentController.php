<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $reason = array_key_exists((string) $request->query('reason'), StockAdjustment::REASONS) ? $request->query('reason') : null;

        $adjustments = StockAdjustment::with(['warehouse:id,name', 'items'])
            ->when($reason, fn ($q) => $q->where('reason', $reason))
            ->when($search !== '', fn ($q) => $q->where('adjustment_no', 'like', "%{$search}%"))
            ->latest('adjustment_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.stock-adjustments.index', compact('adjustments', 'search', 'reason'));
    }

    public function create(): View
    {
        return view('admin.stock-adjustments.form', $this->formData(new StockAdjustment([
            'adjustment_date' => today(),
            'warehouse_id' => Warehouse::default()->id,
            'reason' => 'damaged',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $adjustment = $this->inventory->saveAdjustment($this->validated($request));

        return redirect()->route('admin.stock-adjustments.show', $adjustment)->with('status', "{$adjustment->adjustment_no} saved.");
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        $stockAdjustment->load(['warehouse', 'creator:id,name', 'items.product.unit']);

        return view('admin.stock-adjustments.show', ['adjustment' => $stockAdjustment]);
    }

    /** Count corrections depend on stock at the moment of counting, so they're delete-and-redo only. */
    public function edit(StockAdjustment $stockAdjustment): View|RedirectResponse
    {
        if ($stockAdjustment->reason === 'count') {
            return redirect()->route('admin.stock-adjustments.show', $stockAdjustment)
                ->withErrors(['adjustment' => 'A stock count can\'t be edited. Delete it and count again.']);
        }

        $stockAdjustment->load('items');

        return view('admin.stock-adjustments.form', $this->formData($stockAdjustment));
    }

    public function update(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->inventory->saveAdjustment($this->validated($request), $stockAdjustment);

        return redirect()->route('admin.stock-adjustments.show', $stockAdjustment)->with('status', "{$stockAdjustment->adjustment_no} updated.");
    }

    public function destroy(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->inventory->deleteAdjustment($stockAdjustment);

        return redirect()->route('admin.stock-adjustments.index')->with('status', "{$stockAdjustment->adjustment_no} deleted and reversed.");
    }

    private function formData(StockAdjustment $adjustment): array
    {
        return [
            'adjustment' => $adjustment,
            'warehouses' => Warehouse::where('is_active', true)->orWhere('id', $adjustment->warehouse_id)->orderByDesc('is_default')->orderBy('name')->get(),
            'productOptions' => PurchaseController::productOptions(),
            'stockMap' => StockTransferController::stockMap(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', Rule::in(array_keys(StockAdjustment::REASONS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.direction' => ['nullable', Rule::in(['add', 'remove'])],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.counted' => ['nullable', 'numeric', 'min:0'],
        ], [
            'adjustment_date.before_or_equal' => 'The date can\'t be in the future.',
        ]);
    }
}
