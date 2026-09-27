@php $isEdit = $payment->exists; @endphp
<x-layouts.admin :title="$isEdit ? 'Edit '.$payment->payment_no : 'Record payment'">
    <x-back-link :href="$payment->supplier_id ? route('admin.suppliers.show', $payment->supplier_id) : route('admin.supplier-payments.index')">
        {{ $payment->supplier_id ? 'Supplier ledger' : 'All payments' }}
    </x-back-link>

    <form method="POST" action="{{ $isEdit ? route('admin.supplier-payments.update', $payment) : route('admin.supplier-payments.store') }}" class="card max-w-2xl"
          x-data="{ supplier: @js((string) old('supplier_id', $payment->supplier_id ?? '')), grn: @js((string) old('grn_id', $payment->grn_id ?? '')), grns: @js($grns),
                    get open() { return this.grns.filter(g => String(g.supplier_id) === this.supplier) },
                    get due() { return this.grns.find(g => String(g.id) === this.grn)?.due ?? null } }">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <div class="px-5">
            <x-form-row label="Supplier" for="supplier_id" :required="true">
                <select id="supplier_id" name="supplier_id" required class="input" x-model="supplier" @change="grn = ''">
                    <option value="">Choose a supplier</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id', $payment->supplier_id) == $s->id)>{{ $s->displayName() }}</option>
                    @endforeach
                </select>
            </x-form-row>
            <x-form-row label="Against GRN" for="grn_id" hint="Leave as 'On account' for an advance or a lump-sum payment.">
                <select id="grn_id" name="grn_id" class="input" x-model="grn">
                    <option value="">On account (no specific GRN)</option>
                    <template x-for="g in open" :key="g.id">
                        <option :value="g.id" x-text="g.label + ' - due ' + Number(g.due).toFixed(2)" :selected="String(g.id) === grn"></option>
                    </template>
                </select>
                <p class="text-xs text-zinc-500 mt-1.5" x-show="supplier && open.length === 0" x-cloak>No unpaid GRNs for this supplier.</p>
            </x-form-row>
            <x-form-row label="Amount" for="amount" :required="true">
                <div class="flex flex-wrap items-center gap-3">
                    <input id="amount" type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $payment->amount) }}" class="input" style="max-width:12rem" x-ref="amount">
                    <button type="button" x-show="due !== null && due > 0" x-cloak @click="$refs.amount.value = Number(due).toFixed(2)" class="text-sm text-primary-600 font-medium hover:underline underline-offset-4"
                            x-text="'Pay full due (' + Number(due).toFixed(2) + ')'"></button>
                </div>
            </x-form-row>
            <x-form-row label="Date" for="payment_date" :required="true">
                <input id="payment_date" type="date" name="payment_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}" class="input" style="max-width:12rem">
            </x-form-row>
            <x-form-row label="Method" for="method" :required="true">
                <select id="method" name="method" class="input" style="max-width:20rem">
                    @foreach (\App\Models\SupplierPayment::METHODS as $key => $label)
                        <option value="{{ $key }}" @selected(old('method', $payment->method) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-form-row>
            <x-form-row label="Reference" for="reference" hint="Cheque number or transaction ID.">
                <input id="reference" name="reference" maxlength="100" value="{{ old('reference', $payment->reference) }}" class="input">
            </x-form-row>
            <x-form-row label="Notes" for="notes">
                <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $payment->notes) }}</textarea>
            </x-form-row>
        </div>
        <div class="px-5 py-4 border-t border-zinc-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Record payment' }}</button>
                <a href="{{ route('admin.supplier-payments.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

    @if ($isEdit)
        <div class="card mt-6 p-5 flex flex-wrap items-center justify-between gap-4 max-w-2xl">
            <div>
                <h2 class="text-sm font-semibold">Delete this payment</h2>
                <p class="text-sm text-zinc-500 mt-0.5">The amount goes back onto what you owe {{ $payment->supplier?->displayName() }}.</p>
            </div>
            <x-delete-button variant="danger" label="Delete payment" :action="route('admin.supplier-payments.destroy', $payment)" :confirm="'Delete '.$payment->payment_no.'?'" />
        </div>
    @endif
</x-layouts.admin>
