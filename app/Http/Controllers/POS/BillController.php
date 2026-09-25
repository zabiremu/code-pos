<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function __construct(private BillingService $billing)
    {
    }

    /** Generates a bill for an order (optionally a subset of items, for split billing). */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['exists:order_items,id'],
            'discount_code' => ['nullable', 'string'],
            'service_charge_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $discount = isset($data['discount_code'])
            ? Discount::where('code', $data['discount_code'])->first()
            : null;

        $bill = $this->billing->createBill(
            $order,
            isset($data['item_ids']) ? collect($data['item_ids']) : null,
            $discount,
            $data['service_charge_rate'] ?? 0,
        );

        $order->update(['status' => 'billed']);

        return redirect()->route('pos.bills.show', $bill);
    }

    public function show(\App\Models\Bill $bill): View
    {
        $bill->load(['order.table', 'payments', 'discount']);

        return view('pos.bills.show', compact('bill'));
    }
}
