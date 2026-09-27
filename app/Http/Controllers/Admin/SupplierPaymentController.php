<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Money paid to suppliers, against a GRN or on account. */
class SupplierPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $payments = SupplierPayment::with(['supplier:id,name,company_name', 'grn:id,grn_no'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('payment_no', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($s) => $s->search($search))))
            ->latest('payment_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.supplier-payments.index', compact('payments', 'search'));
    }

    /** ?supplier_id=… and/or ?grn_id=… pre-select what's being paid. */
    public function create(Request $request): View
    {
        $grn = $request->filled('grn_id') ? Grn::find($request->integer('grn_id')) : null;

        $payment = new SupplierPayment([
            'payment_date' => today(),
            'method' => 'cash',
            'supplier_id' => $grn?->supplier_id ?? ($request->filled('supplier_id') ? $request->integer('supplier_id') : null),
            'grn_id' => $grn?->id,
            'amount' => $grn ? max($grn->due(), 0) : null,
        ]);

        return view('admin.supplier-payments.form', $this->formData($payment));
    }

    public function store(Request $request): RedirectResponse
    {
        $payment = DB::transaction(function () use ($request) {
            $payment = SupplierPayment::create($this->validated($request) + ['created_by' => auth()->id()]);
            $payment->update(['payment_no' => 'PAY-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT)]);

            return $payment;
        });

        return redirect()->route('admin.suppliers.show', $payment->supplier_id)
            ->with('status', "{$payment->payment_no}: ".number_format((float) $payment->amount, 2).' paid to '.$payment->supplier->displayName().'.');
    }

    public function edit(SupplierPayment $supplierPayment): View
    {
        return view('admin.supplier-payments.form', $this->formData($supplierPayment));
    }

    public function update(Request $request, SupplierPayment $supplierPayment): RedirectResponse
    {
        $supplierPayment->update($this->validated($request, $supplierPayment));

        return redirect()->route('admin.suppliers.show', $supplierPayment->supplier_id)->with('status', "{$supplierPayment->payment_no} updated.");
    }

    public function destroy(SupplierPayment $supplierPayment): RedirectResponse
    {
        $supplierId = $supplierPayment->supplier_id;
        $supplierPayment->delete();

        return redirect()->route('admin.suppliers.show', $supplierId)->with('status', "{$supplierPayment->payment_no} deleted.");
    }

    private function formData(SupplierPayment $payment): array
    {
        $suppliers = Supplier::where('is_active', true)->orWhere('id', $payment->supplier_id)->orderBy('company_name')->orderBy('name')->get();

        // Open GRNs per supplier, for the "against GRN" picker.
        $grns = Grn::with('returns:id,grn_id,total')->withSum('payments', 'amount')
            ->whereIn('supplier_id', $suppliers->pluck('id'))
            ->latest('received_date')->get()
            ->map(function (Grn $g) use ($payment) {
                $payable = (float) $g->total - (float) $g->returns->sum('total');
                $due = $payable - (float) $g->payments_sum_amount + ($payment->grn_id === $g->id ? (float) $payment->amount : 0);

                return ['id' => $g->id, 'supplier_id' => $g->supplier_id, 'label' => $g->grn_no.' ('.$g->received_date->format('d M Y').')', 'due' => round($due, 2)];
            })
            ->filter(fn ($g) => $g['due'] > 0 || $g['id'] === $payment->grn_id)
            ->values();

        return ['payment' => $payment, 'suppliers' => $suppliers, 'grns' => $grns];
    }

    private function validated(Request $request, ?SupplierPayment $payment = null): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'grn_id' => ['nullable', Rule::exists('grns', 'id')->where('supplier_id', $request->input('supplier_id'))],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'method' => ['required', Rule::in(array_keys(SupplierPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'grn_id.exists' => 'That GRN belongs to a different supplier.',
            'amount.min' => 'Enter an amount above zero.',
            'payment_date.before_or_equal' => 'The payment date can\'t be in the future.',
        ]);

        if (! empty($data['grn_id'])) {
            $grn = Grn::findOrFail($data['grn_id']);
            $due = $grn->due() + ($payment?->grn_id === $grn->id ? (float) $payment->amount : 0);
            if ((float) $data['amount'] > $due + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => 'Only '.number_format($due, 2)." is due on {$grn->grn_no}. Pay the rest without choosing a GRN to record it as an advance.",
                ]);
            }
        }

        return $data;
    }
}
