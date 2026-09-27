@php
    $isEdit = $transfer->exists;
    $lines = old('items', $isEdit ? $transfer->items->map(fn ($i) => ['product_id' => $i->product_id, 'quantity' => (float) $i->quantity])->all() : []);
    // When editing, this transfer's own quantities are still sitting in the destination; add them back to "available" at the source.
    $mine = $isEdit ? $transfer->items->mapWithKeys(fn ($i) => [$i->product_id => (float) $i->quantity])->all() : [];
@endphp
<x-layouts.admin :title="$isEdit ? 'Edit '.$transfer->transfer_no : 'New stock transfer'">
    <x-back-link :href="$isEdit ? route('admin.stock-transfers.show', $transfer) : route('admin.stock-transfers.index')">{{ $isEdit ? $transfer->transfer_no : 'All transfers' }}</x-back-link>

    <form method="POST" action="{{ $isEdit ? route('admin.stock-transfers.update', $transfer) : route('admin.stock-transfers.store') }}" class="space-y-6"
          x-data="Object.assign(lineItems({ products: @js($productOptions), lines: @js(array_values($lines)) }), {
                from: @js((string) old('from_warehouse_id', $transfer->from_warehouse_id)),
                originalFrom: @js((string) $transfer->from_warehouse_id),
                stock: @js((object) $stockMap), mine: @js((object) $mine),
                available(row) {
                    const base = (this.stock[this.from] || {})[row.product_id] || 0;
                    return base + (this.from === this.originalFrom ? (this.mine[row.product_id] || 0) : 0);
                },
            })">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="card" aria-labelledby="route-heading">
            <div class="card-body !pb-0"><h2 id="route-heading" class="text-base font-semibold">Route</h2></div>
            <div class="px-5 grid grid-cols-1 lg:grid-cols-2 lg:gap-x-8">
                <x-form-row label="From" for="from_warehouse_id" :required="true" class="lg:!border-t-0">
                    <select id="from_warehouse_id" name="from_warehouse_id" required class="input" x-model="from">
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('from_warehouse_id', $transfer->from_warehouse_id) == $w->id)>{{ $w->label() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="To" for="to_warehouse_id" :required="true" class="lg:!border-t-0">
                    <select id="to_warehouse_id" name="to_warehouse_id" required class="input">
                        <option value="">Choose a warehouse</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('to_warehouse_id', $transfer->to_warehouse_id) == $w->id) :disabled="from === '{{ $w->id }}'">{{ $w->label() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="Date" for="transfer_date" :required="true">
                    <input id="transfer_date" type="date" name="transfer_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('transfer_date', $transfer->transfer_date?->format('Y-m-d')) }}" class="input">
                </x-form-row>
                <x-form-row label="Notes" for="notes">
                    <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $transfer->notes) }}</textarea>
                </x-form-row>
            </div>
        </section>

        <section class="card overflow-hidden" aria-labelledby="items-heading">
            <div class="px-5 pt-5 pb-3"><h2 id="items-heading" class="text-base font-semibold">Products to move</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-2.5 font-medium" style="min-width:16rem">Product</th>
                            <th class="px-3 py-2.5 font-medium text-right" style="width:9rem">Available</th>
                            <th class="px-3 py-2.5 font-medium text-right" style="width:10rem">Move</th>
                            <th class="px-3 py-2.5" style="width:3rem"><span class="sr-only">Remove</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <template x-for="(row, i) in rows" :key="row.key">
                            <tr class="align-top">
                                <td class="px-5 py-2.5">
                                    <select class="input" data-line-product :name="'items[' + i + '][product_id]'" x-model="row.product_id" :aria-label="'Product, line ' + (i + 1)">
                                        <option value="">Choose a product</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.label" :selected="String(p.id) === String(row.product_id)"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5 pt-4 text-right tabular-nums" :class="row.product_id && Number(row.quantity) > available(row) ? 'text-primary-600 font-semibold' : 'text-zinc-600'"
                                    x-text="row.product_id ? Number(available(row)) + ' ' + (product(row.product_id)?.unit ?? '') : ''"></td>
                                <td class="px-3 py-2.5">
                                    <input type="number" min="0" :step="step(row)" class="input text-right" :name="'items[' + i + '][quantity]'" x-model.number="row.quantity" :aria-label="'Quantity, line ' + (i + 1)">
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <button type="button" @click="remove(i)" class="p-2 rounded-lg text-zinc-400 hover:text-primary-600 hover:bg-primary-50" :aria-label="'Remove line ' + (i + 1)">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-zinc-100 flex flex-wrap items-center justify-between gap-3">
                <button type="button" @click="add()" class="btn-secondary">Add line</button>
                @error('items')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Move stock' }}</button>
            <a href="{{ route('admin.stock-transfers.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
