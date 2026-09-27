<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrnReturn;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin > Suppliers: the people and companies the shop buys stock from. */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : 'all';

        $suppliers = Supplier::query()
            ->withSum('grns as received_total', 'total')
            ->withSum('payments as paid_total', 'amount')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status !== 'all', fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('company_name')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Supplier::count(),
            'active' => Supplier::where('is_active', true)->count(),
            'inactive' => Supplier::where('is_active', false)->count(),
        ];

        $returnedBySupplier = GrnReturn::join('grns', 'grns.id', '=', 'grn_returns.grn_id')
            ->whereIn('grns.supplier_id', $suppliers->pluck('id'))
            ->groupBy('grns.supplier_id')
            ->selectRaw('grns.supplier_id, sum(grn_returns.total) as returned')
            ->pluck('returned', 'supplier_id');

        return view('admin.suppliers.index', compact('suppliers', 'search', 'status', 'counts', 'returnedBySupplier'));
    }

    /** Ledger: goods received (owed), returns and payments (reduce what's owed), with a running balance. */
    public function show(Supplier $supplier): View
    {
        $grns = $supplier->grns()->get(['id', 'grn_no', 'received_date', 'total', 'supplier_invoice_no']);
        $returns = GrnReturn::with('grn:id,grn_no')->whereIn('grn_id', $grns->pluck('id'))->get();
        $payments = $supplier->payments()->with('grn:id,grn_no')->get();

        $entries = collect()
            ->concat($grns->map(fn ($g) => [
                'date' => $g->received_date, 'sort' => 1, 'type' => 'Goods received', 'no' => $g->grn_no,
                'url' => route('admin.grns.show', $g), 'detail' => $g->supplier_invoice_no ? "Invoice {$g->supplier_invoice_no}" : null,
                'debit' => 0.0, 'credit' => (float) $g->total,
            ]))
            ->concat($returns->map(fn ($r) => [
                'date' => $r->return_date, 'sort' => 2, 'type' => 'Returned', 'no' => $r->return_no,
                'url' => route('admin.grn-returns.show', $r), 'detail' => 'From '.$r->grn?->grn_no,
                'debit' => (float) $r->total, 'credit' => 0.0,
            ]))
            ->concat($payments->map(fn ($p) => [
                'date' => $p->payment_date, 'sort' => 3, 'type' => 'Payment', 'no' => $p->payment_no,
                'url' => route('admin.supplier-payments.edit', $p), 'detail' => $p->methodLabel().($p->grn ? ' for '.$p->grn->grn_no : ' (on account)').($p->reference ? ", ref {$p->reference}" : ''),
                'debit' => (float) $p->amount, 'credit' => 0.0,
            ]))
            ->sortBy([['date', 'asc'], ['sort', 'asc']])
            ->values();

        $balance = 0.0;
        $entries = $entries->map(function ($e) use (&$balance) {
            $balance += $e['credit'] - $e['debit'];

            return $e + ['balance' => round($balance, 2)];
        });

        return view('admin.suppliers.show', [
            'supplier' => $supplier,
            'entries' => $entries->reverse()->values(),
            'balance' => round($balance, 2),
            'totals' => [
                'received' => (float) $grns->sum('total'),
                'returned' => (float) $returns->sum('total'),
                'paid' => (float) $payments->sum('amount'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.suppliers.create', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('admin.suppliers.index')
            ->with('status', 'Supplier "'.$supplier->displayName().'" added.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('admin.suppliers.show', $supplier)
            ->with('status', 'Supplier "'.$supplier->displayName().'" updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $name = $supplier->displayName();

        if ($supplier->purchases()->exists() || $supplier->grns()->exists() || $supplier->payments()->exists()) {
            return back()->withErrors(['supplier' => "\"{$name}\" has purchases or GRNs on record, so it can't be deleted. Mark it inactive instead."]);
        }
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier "'.$name.'" deleted.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('suppliers', 'email')->ignore($supplier)],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.unique' => 'Another supplier already uses this email.',
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
