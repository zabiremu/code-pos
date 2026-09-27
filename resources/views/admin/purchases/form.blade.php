@php
    $isEdit = $purchase->exists;
    $lines = old('items', $isEdit
        ? $purchase->items->map(fn ($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity, 'unit_cost' => (float) $i->unit_cost])->all()
        : []);
    $extras = [
        'discount' => (float) old('discount', $purchase->discount ?? 0),
        'tax' => (float) old('tax', $purchase->tax ?? 0),
        'shipping' => (float) old('shipping', $purchase->shipping ?? 0),
    ];
@endphp
<x-layouts.admin :title="$isEdit ? 'Edit '.$purchase->reference_no : 'New purchase'">
    <x-back-link :href="$isEdit ? route('admin.purchases.show', $purchase) : route('admin.purchases.index')">{{ $isEdit ? $purchase->reference_no : 'All purchases' }}</x-back-link>

    @if ($suppliers->isEmpty())
        <div class="card p-6 max-w-xl">
            <p class="text-zinc-700">Add a supplier before creating a purchase.</p>
            <a href="{{ route('admin.suppliers.create') }}" class="btn-primary mt-4">Add supplier</a>
        </div>
    @else
    <form method="POST" action="{{ $isEdit ? route('admin.purchases.update', $purchase) : route('admin.purchases.store') }}"
          x-data="lineItems({ products: @js($productOptions), lines: @js(array_values($lines)), extras: @js($extras) })" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="card" aria-labelledby="order-heading">
            <div class="card-body !pb-0"><h2 id="order-heading" class="text-base font-semibold">Order</h2></div>
            <div class="px-5 grid grid-cols-1 lg:grid-cols-2 lg:gap-x-8">
                <x-form-row label="Supplier" for="supplier_id" :required="true" class="lg:!border-t-0">
                    <select id="supplier_id" name="supplier_id" required class="input">
                        <option value="">Choose a supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', $purchase->supplier_id) == $s->id)>{{ $s->displayName() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="Deliver to" for="warehouse_id" :required="true" class="lg:!border-t-0">
                    <select id="warehouse_id" name="warehouse_id" required class="input">
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id', $purchase->warehouse_id) == $w->id)>{{ $w->label() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="Purchase date" for="purchase_date" :required="true">
                    <input id="purchase_date" type="date" name="purchase_date" required value="{{ old('purchase_date', $purchase->purchase_date?->format('Y-m-d')) }}" class="input">
                </x-form-row>
                <x-form-row label="Expected by" for="expected_date">
                    <input id="expected_date" type="date" name="expected_date" value="{{ old('expected_date', $purchase->expected_date?->format('Y-m-d')) }}" class="input">
                </x-form-row>
                <x-form-row label="Status" for="status" hint="Draft while you're still deciding; Ordered once sent to the supplier.">
                    <select id="status" name="status" class="input">
                        <option value="ordered" @selected(old('status', $purchase->status) === 'ordered')>Ordered</option>
                        <option value="draft" @selected(old('status', $purchase->status) === 'draft')>Draft</option>
                    </select>
                </x-form-row>
                <x-form-row label="Notes" for="notes">
                    <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $purchase->notes) }}</textarea>
                </x-form-row>
            </div>
        </section>

        <section class="card overflow-hidden" aria-labelledby="items-heading">
            <div class="px-5 pt-5 pb-3"><h2 id="items-heading" class="text-base font-semibold">Items</h2></div>
            @include('admin.purchasing._lines')

            <div class="border-t border-zinc-100 px-5 py-4 flex justify-end">
                <dl class="w-full max-w-sm text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd class="tabular-nums" x-text="money(subtotal)"></dd></div>
                    @foreach (['discount' => 'Discount', 'tax' => 'Tax', 'shipping' => 'Shipping'] as $key => $label)
                        <div class="flex items-center justify-between gap-3">
                            <dt><label for="{{ $key }}" class="text-zinc-500">{{ $label }}{{ $key === 'discount' ? ' (−)' : ' (+)' }}</label></dt>
                            <dd><input id="{{ $key }}" type="number" step="0.01" min="0" name="{{ $key }}" x-model.number="extras.{{ $key }}" class="input text-right" style="width:9rem"></dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums" x-text="money(total)"></dd></div>
                </dl>
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Save purchase' }}</button>
            <a href="{{ $isEdit ? route('admin.purchases.show', $purchase) : route('admin.purchases.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
    @endif
</x-layouts.admin>
