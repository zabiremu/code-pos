<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\SaleReturn;
use App\Services\CustomerAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Customers bringing goods back. */
class SaleReturnController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $returns = SaleReturn::with(['customer:id,name', 'bill:id'])->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('return_no', 'like', "%{$search}%")->orWhere('bill_id', ltrim($search, '#'))))
            ->latest('return_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.sale-returns.index', compact('returns', 'search'));
    }

    /** Step 1: find the receipt (?bill=…). Step 2: pick quantities. */
    public function create(Request $request): View
    {
        $bill = null;
        $notFound = false;
        if ($request->filled('bill')) {
            $bill = Bill::with(['sale.items.product.unit', 'sale.customer', 'payments', 'saleReturns'])->find((int) ltrim((string) $request->query('bill'), '#'));
            $notFound = ! $bill;
        }

        return view('admin.sale-returns.form', [
            'bill' => $bill,
            'notFound' => $notFound,
            'recentBills' => $bill ? collect() : Bill::with('sale.customer:id,name')->latest('id')->take(15)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bill_id' => ['required', 'exists:bills,id'],
            'return_date' => ['required', 'date', 'before_or_equal:today'],
            'refund_method' => ['required', Rule::in(array_keys(SaleReturn::REFUND_METHODS))],
            'reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['restock'] = $request->boolean('restock');

        $bill = Bill::findOrFail($data['bill_id']);
        $return = $this->accounts->saveReturn($bill->sale, $data);

        return redirect()->route('admin.sale-returns.show', $return)
            ->with('status', "{$return->return_no} saved: ".number_format((float) $return->total, 2).' '.($return->refund_method === 'adjust_due' ? 'taken off what they owe.' : 'to refund.'));
    }

    public function show(SaleReturn $saleReturn): View
    {
        $saleReturn->load(['customer', 'bill', 'creator:id,name', 'items.product.unit']);

        return view('admin.sale-returns.show', ['return' => $saleReturn]);
    }

    public function destroy(SaleReturn $saleReturn): RedirectResponse
    {
        $this->accounts->deleteReturn($saleReturn);

        return redirect()->route('admin.sale-returns.index')->with('status', "{$saleReturn->return_no} deleted and reversed.");
    }
}
