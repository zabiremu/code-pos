<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $transfers = StockTransfer::with(['fromWarehouse:id,name', 'toWarehouse:id,name'])->withCount('items')
            ->when($search !== '', fn ($q) => $q->where('transfer_no', 'like', "%{$search}%"))
            ->latest('transfer_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.stock-transfers.index', compact('transfers', 'search'));
    }

    public function create(): View|RedirectResponse
    {
        if (Warehouse::where('is_active', true)->count() < 2) {
            return redirect()->route('admin.warehouses.create')
                ->withErrors(['warehouse' => 'You need at least two active warehouses to transfer stock. Add another one first.']);
        }

        return view('admin.stock-transfers.form', $this->formData(new StockTransfer([
            'transfer_date' => today(),
            'from_warehouse_id' => Warehouse::default()->id,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $transfer = $this->inventory->saveTransfer($this->validated($request));

        return redirect()->route('admin.stock-transfers.show', $transfer)
            ->with('status', "{$transfer->transfer_no} saved. Stock moved from {$transfer->fromWarehouse->name} to {$transfer->toWarehouse->name}.");
    }

    public function show(StockTransfer $stockTransfer): View
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'creator:id,name', 'items.product.unit']);

        return view('admin.stock-transfers.show', ['transfer' => $stockTransfer]);
    }

    public function edit(StockTransfer $stockTransfer): View
    {
        $stockTransfer->load('items');

        return view('admin.stock-transfers.form', $this->formData($stockTransfer));
    }

    public function update(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->inventory->saveTransfer($this->validated($request), $stockTransfer);

        return redirect()->route('admin.stock-transfers.show', $stockTransfer)->with('status', "{$stockTransfer->transfer_no} updated.");
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->inventory->deleteTransfer($stockTransfer);

        return redirect()->route('admin.stock-transfers.index')->with('status', "{$stockTransfer->transfer_no} deleted and the stock moved back.");
    }

    private function formData(StockTransfer $transfer): array
    {
        return [
            'transfer' => $transfer,
            'warehouses' => Warehouse::where('is_active', true)
                ->orWhereIn('id', array_filter([$transfer->from_warehouse_id, $transfer->to_warehouse_id]))
                ->orderByDesc('is_default')->orderBy('name')->get(),
            'productOptions' => PurchaseController::productOptions(),
            'stockMap' => self::stockMap(),
        ];
    }

    /** { warehouse_id: { product_id: qty } } so forms can show what's available. */
    public static function stockMap(): array
    {
        $map = [];
        foreach (WarehouseStock::where('quantity', '!=', 0)->get(['warehouse_id', 'product_id', 'quantity']) as $row) {
            $map[$row->warehouse_id][$row->product_id] = (float) $row->quantity;
        }

        return $map;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ], [
            'to_warehouse_id.different' => 'Choose a different warehouse to send the stock to.',
            'transfer_date.before_or_equal' => 'The transfer date can\'t be in the future.',
        ]);
    }
}
