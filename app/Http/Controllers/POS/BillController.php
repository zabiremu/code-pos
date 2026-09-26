<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Discount;
use App\Models\Sale;
use App\Services\BillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function __construct(private BillingService $billing)
    {
    }

    /** Generates a bill for a sale (optionally a subset of items, for split billing). */
    public function store(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['exists:sale_items,id'],
            'discount_code' => ['nullable', 'string'],
            'service_charge_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $discount = isset($data['discount_code'])
            ? Discount::where('code', $data['discount_code'])->first()
            : null;

        $bill = $this->billing->createBill(
            $sale,
            isset($data['item_ids']) ? collect($data['item_ids']) : null,
            $discount,
            $data['service_charge_rate'] ?? 0,
        );

        $sale->update(['status' => 'billed']);

        return redirect()->route('pos.bills.show', $bill);
    }

    public function show(Bill $bill): View
    {
        $bill->load(['sale.items.product', 'payments', 'discount']);

        return view('pos.bills.show', compact('bill'));
    }
}
