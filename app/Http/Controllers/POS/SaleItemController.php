<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaleItemController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    /** Add one line item to an open sale, deducting stock immediately. */
    public function store(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        $saleItem = $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
            'unit_price' => $product->base_price,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->stock->deductForSaleItem($saleItem);

        return redirect()->route('pos.sales.show', $sale)->with('status', 'Item added.');
    }

    /** Remove a line item from a still-open sale, restoring any deducted stock. */
    public function destroy(SaleItem $saleItem): RedirectResponse
    {
        $sale = $saleItem->sale;

        abort_if($sale->status !== 'open', 403, 'This sale can no longer be changed.');

        $this->stock->restoreForRemovedSaleItem($saleItem);
        $saleItem->delete();

        return back()->with('status', 'Item removed.');
    }
}
