@php
    $isEdit = $grn->exists;
    $formLines = old('items', $lines);
@endphp
<x-layouts.admin :title="$isEdit ? 'Edit '.$grn->grn_no : 'Receive goods'">
    <x-back-link :href="$isEdit ? route('admin.grns.show', $grn) : ($purchase ? route('admin.purchases.show', $purchase) : route('admin.grns.index'))">
        {{ $isEdit ? $grn->grn_no : ($purchase ? $purchase->reference_no : 'All GRNs') }}
    </x-back-link>

    <form method="POST" action="{{ $isEdit ? route('admin.grns.update', $grn) : route('admin.grns.store') }}"
          x-data="lineItems({ products: @js($productOptions), lines: @js(array_values($formLines)) })" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <input type="hidden" name="purchase_id" value="{{ old('purchase_id', $grn->purchase_id) }}">

        @if ($purchase)
            <div class="rounded-xl bg-primary-50 ring-1 ring-primary-600/10 px-5 py-3 text-sm text-primary-900">
                Receiving against <a href="{{ route('admin.purchases.show', $purchase) }}" class="font-semibold underline underline-offset-4">{{ $purchase->reference_no }}</a>.
                Quantities are pre-filled with what's still to arrive - change them to match what actually came.
            </div>
        @endif

        <section class="card" aria-labelledby="delivery-heading">
            <div class="card-body !pb-0"><h2 id="delivery-heading" class="text-base font-semibold">Delivery</h2></div>
            <div class="px-5 grid grid-cols-1 lg:grid-cols-2 lg:gap-x-8">
                <x-form-row label="Supplier" for="supplier_id" :required="true" class="lg:!border-t-0">
                    <select id="supplier_id" name="supplier_id" required class="input">
                        <option value="">Choose a supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', $grn->supplier_id) == $s->id)>{{ $s->displayName() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="Into warehouse" for="warehouse_id" :required="true" class="lg:!border-t-0">
                    <select id="warehouse_id" name="warehouse_id" required class="input">
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id', $grn->warehouse_id) == $w->id)>{{ $w->label() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="Received on" for="received_date" :required="true">
                    <input id="received_date" type="date" name="received_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('received_date', $grn->received_date?->format('Y-m-d')) }}" class="input">
                </x-form-row>
                <x-form-row label="Supplier invoice no." for="supplier_invoice_no">
                    <input id="supplier_invoice_no" name="supplier_invoice_no" maxlength="100" value="{{ old('supplier_invoice_no', $grn->supplier_invoice_no) }}" class="input">
                </x-form-row>
                <x-form-row label="Notes" for="notes" class="lg:col-span-2">
                    <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $grn->notes) }}</textarea>
                </x-form-row>
            </div>
        </section>

        <section class="card overflow-hidden" aria-labelledby="items-heading">
            <div class="px-5 pt-5 pb-3 flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="items-heading" class="text-base font-semibold">Items received</h2>
                <p class="text-xs text-zinc-500">Each product's purchase price updates to the unit cost entered here.</p>
            </div>
            @include('admin.purchasing._lines', ['showOrdered' => (bool) $purchase])
            <div class="border-t border-zinc-100 px-5 py-4 flex justify-end">
                <dl class="w-full max-w-xs text-sm">
                    <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd class="tabular-nums" x-text="money(total)"></dd></div>
                </dl>
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Receive into stock' }}</button>
            <a href="{{ $isEdit ? route('admin.grns.show', $grn) : route('admin.grns.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
