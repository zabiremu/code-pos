{{--
    Editable line items for purchases and GRNs (Alpine "lineItems" in
    resources/js/line-items.js). $showOrdered adds a "still to receive"
    hint column for GRNs raised from a purchase.
--}}
@php $showOrdered = $showOrdered ?? false; @endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                <th class="px-5 py-2.5 font-medium" style="min-width:16rem">Product</th>
                <th class="px-3 py-2.5 font-medium text-right" style="width:9rem">Quantity</th>
                <th class="px-3 py-2.5 font-medium text-right" style="width:9rem">Unit cost</th>
                <th class="px-3 py-2.5 font-medium text-right" style="width:9rem">Line total</th>
                <th class="px-3 py-2.5" style="width:3rem"><span class="sr-only">Remove</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
            <template x-for="(row, i) in rows" :key="row.key">
                <tr class="align-top">
                    <td class="px-5 py-2.5">
                        <select class="input" data-line-product :name="'items[' + i + '][product_id]'" x-model="row.product_id" @change="pick(row)"
                                :aria-label="'Product, line ' + (i + 1)">
                            <option value="">Choose a product</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.label" :selected="String(p.id) === String(row.product_id)"></option>
                            </template>
                        </select>
                        <input type="hidden" :name="'items[' + i + '][purchase_item_id]'" :value="row.purchase_item_id ?? ''">
                        @if ($showOrdered)
                            <p class="text-xs text-zinc-500 mt-1" x-show="row.ordered !== null && row.ordered !== undefined"
                               x-text="'Still to receive on the order: ' + Number(row.ordered)"></p>
                        @endif
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <input type="number" min="0" :step="step(row)" class="input text-right" :name="'items[' + i + '][quantity]'" x-model.number="row.quantity"
                                   :aria-label="'Quantity, line ' + (i + 1)">
                            <span class="text-xs text-zinc-400 w-7" x-text="product(row.product_id)?.unit ?? ''"></span>
                        </div>
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" min="0" step="0.01" class="input text-right" :name="'items[' + i + '][unit_cost]'" x-model.number="row.unit_cost"
                               :aria-label="'Unit cost, line ' + (i + 1)">
                    </td>
                    <td class="px-3 py-2.5 text-right tabular-nums pt-4" x-text="money(lineTotal(row))"></td>
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
    <button type="button" @click="add()" class="btn-secondary">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
        Add line
    </button>
    @error('items')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror
    @foreach ($errors->getMessages() as $key => $messages)
        @if (str_starts_with($key, 'items.'))<p class="text-sm text-primary-600">{{ $messages[0] }}</p>@break @endif
    @endforeach
</div>
