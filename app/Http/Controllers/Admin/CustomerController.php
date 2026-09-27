<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $filter = $request->query('filter') === 'due' ? 'due' : 'all';

        $customers = Customer::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->withCount('sales')
            ->orderByDesc('is_active')->orderBy('name')
            ->get();

        // Due per customer in two queries rather than one per row.
        $billed = Bill::join('sales', 'sales.id', '=', 'bills.sale_id')->whereNotNull('sales.customer_id')
            ->groupBy('sales.customer_id')->selectRaw('sales.customer_id, sum(bills.grand_total) as t')->pluck('t', 'customer_id');
        $paid = Payment::join('bills', 'bills.id', '=', 'payments.bill_id')->join('sales', 'sales.id', '=', 'bills.sale_id')->whereNotNull('sales.customer_id')
            ->groupBy('sales.customer_id')->selectRaw('sales.customer_id, sum(payments.amount) as t')->pluck('t', 'customer_id');
        $adjusted = \App\Models\SaleReturn::where('refund_method', 'adjust_due')->whereNotNull('customer_id')
            ->groupBy('customer_id')->selectRaw('customer_id, sum(total) as t')->pluck('t', 'customer_id');

        $customers->each(fn ($c) => $c->setAttribute('due_amount', round((float) ($billed[$c->id] ?? 0) - (float) ($paid[$c->id] ?? 0) - (float) ($adjusted[$c->id] ?? 0), 2)));

        $totalDue = $customers->sum(fn ($c) => max($c->due_amount, 0));
        if ($filter === 'due') {
            $customers = $customers->filter(fn ($c) => $c->due_amount > 0.004)->sortByDesc('due_amount')->values();
        }

        $page = max($request->integer('page', 1), 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator($customers->forPage($page, 25)->values(), $customers->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.customers.index', ['customers' => $paginated, 'search' => $search, 'filter' => $filter, 'totalDue' => $totalDue]);
    }

    public function create(): View
    {
        return view('admin.customers.form', ['customer' => new Customer(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validated($request));

        return redirect()->route('admin.customers.show', $customer)->with('status', "Customer \"{$customer->name}\" added.");
    }

    /** Account: every bill, payment and return with a running balance of what they owe. */
    public function show(Customer $customer): View
    {
        $bills = $customer->bills()->with(['sale:id,created_at', 'payments.receipt', 'saleReturns'])->orderBy('bills.created_at')->get();

        $entries = collect();
        foreach ($bills as $bill) {
            $entries->push(['date' => $bill->created_at, 'sort' => 1, 'type' => 'Sale', 'no' => '#'.$bill->id,
                'url' => route('pos.register.receipt', $bill), 'detail' => null, 'owed' => (float) $bill->grand_total, 'paid' => 0.0]);
            foreach ($bill->payments->whereNull('customer_receipt_id') as $p) {
                $entries->push(['date' => $p->created_at, 'sort' => 2, 'type' => 'Paid at till', 'no' => '#'.$bill->id,
                    'url' => route('pos.register.receipt', $bill), 'detail' => null, 'owed' => 0.0, 'paid' => (float) $p->amount]);
            }
            foreach ($bill->saleReturns->where('refund_method', 'adjust_due') as $r) {
                $entries->push(['date' => $r->created_at, 'sort' => 3, 'type' => 'Returned', 'no' => $r->return_no,
                    'url' => route('admin.sale-returns.show', $r), 'detail' => 'Taken off the due', 'owed' => 0.0, 'paid' => (float) $r->total]);
            }
        }
        foreach ($customer->receipts()->get() as $r) {
            $entries->push(['date' => $r->created_at, 'sort' => 4, 'type' => 'Due collected', 'no' => $r->receipt_no,
                'url' => route('admin.customer-receipts.show', $r), 'detail' => $r->methodLabel().($r->reference ? ", ref {$r->reference}" : ''), 'owed' => 0.0, 'paid' => (float) $r->amount]);
        }

        $balance = 0.0;
        $entries = $entries->sortBy([['date', 'asc'], ['sort', 'asc']])->values()
            ->map(function ($e) use (&$balance) {
                $balance += $e['owed'] - $e['paid'];

                return $e + ['balance' => round($balance, 2)];
            });

        return view('admin.customers.show', [
            'customer' => $customer,
            'entries' => $entries->reverse()->values(),
            'due' => $customer->due(),
            'stats' => ['sales' => $bills->count(), 'spent' => (float) $bills->sum('grand_total'), 'last' => $bills->last()?->created_at],
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return redirect()->route('admin.customers.show', $customer)->with('status', "Customer \"{$customer->name}\" updated.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists() || $customer->receipts()->exists()) {
            return back()->withErrors(['customer' => "\"{$customer->name}\" has sales on record, so they can't be deleted. Mark them inactive instead."]);
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('status', "Customer \"{$customer->name}\" deleted.");
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($customer)],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:1000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['phone.unique' => 'Another customer already has this phone number.']) + ['is_active' => $request->boolean('is_active')];
    }
}
