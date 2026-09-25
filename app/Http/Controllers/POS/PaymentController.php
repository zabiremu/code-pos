<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billing)
    {
    }

    public function store(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:cash,card,mobile_wallet,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->billing->recordPayment(
            $bill,
            $data['method'],
            $data['amount'],
            $data['reference'] ?? null,
            $request->user()->id,
        );

        return back()->with('status', 'Payment recorded.');
    }
}
