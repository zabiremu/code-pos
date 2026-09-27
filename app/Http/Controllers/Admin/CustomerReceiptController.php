<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Services\CustomerAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Collecting what customers owe. */
class CustomerReceiptController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $receipts = CustomerReceipt::with('customer:id,name,phone')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('receipt_no', 'like', "%{$search}%")->orWhereHas('customer', fn ($c) => $c->search($search))))
            ->latest('receipt_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.customer-receipts.index', compact('receipts', 'search'));
    }

    public function create(Request $request): View
    {
        $customer = $request->filled('customer_id') ? Customer::findOrFail($request->integer('customer_id')) : null;

        return view('admin.customer-receipts.form', [
            'customer' => $customer,
            'due' => $customer?->due(),
            'openBills' => $customer?->openBills() ?? collect(),
            'customers' => $customer ? collect() : Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'receipt_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(array_keys(CustomerReceipt::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['amount.min' => 'Enter an amount above zero.']);

        $customer = Customer::findOrFail($data['customer_id']);
        $receipt = $this->accounts->collect($customer, $data);

        return redirect()->route('admin.customers.show', $customer)
            ->with('status', "{$receipt->receipt_no}: ".number_format((float) $receipt->amount, 2)." collected from {$customer->name}.");
    }

    public function show(CustomerReceipt $customerReceipt): View
    {
        $customerReceipt->load(['customer', 'creator:id,name', 'payments.bill']);

        return view('admin.customer-receipts.show', ['receipt' => $customerReceipt]);
    }

    public function destroy(CustomerReceipt $customerReceipt): RedirectResponse
    {
        $customerId = $customerReceipt->customer_id;
        $this->accounts->deleteReceipt($customerReceipt);

        return redirect()->route('admin.customers.show', $customerId)->with('status', "{$customerReceipt->receipt_no} deleted. The amount is owed again.");
    }
}
