@php
    $isEdit = $adjustment->exists;
    $lines = old('items', $isEdit
        ? $adjustment->items->map(fn ($i) => ['product_id' => $i->product_id, 'direction' => $i->quantity < 0 ? 'remove' : 'add', 'quantity' => abs((float) $i->quantity)])->all()
        : []);
    $lines = array_values(array_map(fn ($l) => $l + ['direction' => 'remove', 'counted' => ''], $lines));
@endphp
<x-layouts.admin :title="$isEdit ? 'Edit '.$adjustment->adjustment_no : 'New stock adjustment'">
    <x-back-link :href="$isEdit ? route('admin.stock-adjustments.show', $adjustment) : route('admin.stock-adjustments.index')">{{ $isEdit ? $adjustment->adjustment_no : 'All adjustments' }}</x-back-link>

    <form method="POST" action="{{ $isEdit ? route('admin.stock-adjustments.update', $adjustment) : route('admin.stock-adjustments.store') }}" class="space-y-6"
          x-data="Object.assign(lineItems({ products: @js($productOptions), lines: @js($lines) }), {
                warehouse: @js((string) old('warehouse_id', $adjustment->warehouse_id)),
                reason: @js(old('reason', $adjustment->reason)),
                stock: @js((object) $stockMap),
                system(row) { return (this.stock[this.warehouse] || {})[row.product_id] || 0 },
                diff(row) { return row.counted === '' || row.counted === null ? null : Number(row.counted) - this.system(row) },
            })">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="card">
            <div class="px-5">
                <x-form-row label="Warehouse" for="warehouse_id" :required="true">
                    <select id="warehouse_id" name="warehouse_id" required class="input" x-model="warehouse" style="max-width:24rem">
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id', $adjustment->warehouse_id) == $w->id)>{{ $w->label() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row label="Reason" for="reason" :required="true">
                    <select id="reason" name="reason" class="input" x-model="reason" style="max-width:24rem">
                        @foreach (\App\Models\StockAdjustment::REASONS as $key => $label)
                            @if (! $isEdit || $key !== 'count')
                                <option value="{{ $key }}" @selected(old('reason', $adjustment->reason) === $key)>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-zinc-500 mt-1.5" x-show="reason === 'count'" x-cloak>Enter what you physically counted. The difference from the system is adjusted automatically.</p>
                </x-form-row>
                <x-form-row label="Date" for="adjustment_date" :required="true">
                    <input id="adjustment_date" type="date" name="adjustment_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('adjustment_date', $adjustment->adjustment_date?->format('Y-m-d')) }}" class="input" style="max-width:12rem">
                </x-form-row>
                <x-form-row label="Notes" for="notes">
                    <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $adjustment->notes) }}</textarea>
                </x-form-row>
            </div>
        </section>

        <section class="card overflow-hidden" aria-labelledby="items-heading">
            <div class="px-5 pt-5 pb-3"><h2 id="items-heading" class="text-base font-semibold">Products</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-2.5 font-medium" style="min-width:16rem">Product</th>
                            <th class="px-3 py-2.5 font-medium text-right" style="width:8rem">In system</th>
                            <th class="px-3 py-2.5 font-medium" style="width:9rem" x-show="reason !== 'count'">Change</th>
                            <th class="px-3 py-2.5 font-medium text-right" style="width:9rem" x-text="reason === 'count' ? 'Counted' : 'Quantity'"></th>
                            <th class="px-3 py-2.5 font-medium text-right" style="width:8rem" x-show="reason === 'count'" x-cloak>Difference</th>
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
                                <td class="px-3 py-2.5 pt-4 text-right tabular-nums text-zinc-600" x-text="row.product_id ? Number(system(row)) + ' ' + (product(row.product_id)?.unit ?? '') : ''"></td>
                                <td class="px-3 py-2.5" x-show="reason !== 'count'">
                                    <select class="input" :name="'items[' + i + '][direction]'" x-model="row.direction" :aria-label="'Add or remove, line ' + (i + 1)">
                                        <option value="remove">Remove</option>
                                        <option value="add">Add</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5">
                                    <input x-show="reason !== 'count'" type="number" min="0" :step="step(row)" class="input text-right" :name="reason !== 'count' ? 'items[' + i + '][quantity]' : null" x-model.number="row.quantity" :aria-label="'Quantity, line ' + (i + 1)">
                                    <input x-show="reason === 'count'" x-cloak type="number" min="0" :step="step(row)" class="input text-right" :name="reason === 'count' ? 'items[' + i + '][counted]' : null" x-model="row.counted" :aria-label="'Counted, line ' + (i + 1)">
                                </td>
                                <td class="px-3 py-2.5 pt-4 text-right tabular-nums font-medium" x-show="reason === 'count'" x-cloak
                                    :class="diff(row) < 0 ? 'text-primary-600' : 'text-emerald-700'"
                                    x-text="diff(row) === null ? '' : (diff(row) > 0 ? '+' : '') + Number(diff(row).toFixed(3))"></td>
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
            <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Save adjustment' }}</button>
            <a href="{{ route('admin.stock-adjustments.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
