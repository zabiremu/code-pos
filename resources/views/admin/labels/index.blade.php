{{-- Choose products and how many stickers of each, then print on sheet or roll labels. --}}
@php
    $oldItems = collect(old('items', []))->map(fn ($i) => ['id' => (int) $i['product_id'], 'qty' => (int) $i['qty']])->values();
@endphp
<x-layouts.admin title="Barcode labels">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-zinc-500">Print stickers with each product's barcode. Scanning one at the register adds that product to the sale.</p>
        <form method="POST" action="{{ route('admin.labels.generate-skus') }}">
            @csrf
            <button class="btn-secondary" title="Products without a SKU get a unique number that works as a barcode">Give barcodes to products without one</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.labels.print') }}" target="_blank" class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start"
          x-data="{
              products: @js($products), search: '', lines: [],
              init() {
                  const pre = @js($oldItems->isNotEmpty() ? $oldItems : $preselected->map(fn ($id) => ['id' => $id, 'qty' => 1]));
                  pre.forEach(l => { const p = this.byId(l.id); if (p) this.lines.push({ id: p.id, qty: l.qty || 1 }) });
              },
              byId(id) { return this.products.find(p => p.id === id) },
              get matches() {
                  const q = this.search.trim().toLowerCase();
                  if (!q) return [];
                  return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q)).slice(0, 8);
              },
              add(p) { const l = this.lines.find(l => l.id === p.id); l ? l.qty++ : this.lines.push({ id: p.id, qty: 1 }); this.search = ''; this.$refs.search.focus() },
              get total() { return this.lines.reduce((s, l) => s + (parseInt(l.qty) || 0), 0) },
              get noSku() { return this.lines.filter(l => !this.byId(l.id)?.sku).length },
          }">
        @csrf
        <section class="card overflow-hidden xl:col-span-2" aria-labelledby="which-heading">
            <div class="px-5 pt-5 pb-3">
                <h2 id="which-heading" class="text-base font-semibold mb-3">Which products?</h2>
                <div class="relative">
                    <input x-ref="search" x-model="search" type="search" placeholder="Search name or SKU, then press Enter" aria-label="Find a product" class="input h-11"
                           @keydown.enter.prevent="matches.length && add(matches[0])">
                    <ul x-show="matches.length" x-cloak class="absolute z-10 left-0 right-0 mt-1 bg-white rounded-xl shadow-lg ring-1 ring-zinc-200 divide-y divide-zinc-100 overflow-hidden">
                        <template x-for="p in matches" :key="p.id">
                            <li><button type="button" @click="add(p)" class="w-full text-left px-4 py-2.5 hover:bg-zinc-50 flex justify-between gap-3 text-sm">
                                <span x-text="p.name"></span>
                                <span class="text-zinc-400" x-text="p.sku ?? 'no barcode'"></span>
                            </button></li>
                        </template>
                    </ul>
                </div>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-2.5 font-medium">Product</th>
                        <th class="px-5 py-2.5 font-medium">Barcode</th>
                        <th class="px-5 py-2.5 font-medium text-right" style="width:11rem">Labels</th>
                        <th class="px-3 py-2.5" style="width:3rem"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="(l, i) in lines" :key="l.id">
                        <tr>
                            <td class="px-5 py-2.5" x-text="byId(l.id)?.name"></td>
                            <td class="px-5 py-2.5">
                                <span x-show="byId(l.id)?.sku" class="font-mono text-xs" x-text="byId(l.id)?.sku"></span>
                                <span x-show="!byId(l.id)?.sku" class="text-xs text-primary-600">None yet</span>
                            </td>
                            <td class="px-5 py-2">
                                <input type="hidden" :name="'items[' + i + '][product_id]'" :value="l.id">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" x-show="byId(l.id)?.stock" @click="l.qty = byId(l.id).stock" class="text-xs text-primary-600 hover:underline underline-offset-2 whitespace-nowrap" :title="'One for each item in stock'" x-text="'= stock (' + byId(l.id)?.stock + ')'"></button>
                                    <input type="number" min="1" max="1000" :name="'items[' + i + '][qty]'" x-model.number="l.qty" class="input text-right" style="width:5rem" :aria-label="'Labels for ' + byId(l.id)?.name">
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" @click="lines.splice(i, 1)" class="p-2 rounded-lg text-zinc-400 hover:text-primary-600 hover:bg-primary-50" :aria-label="'Remove ' + byId(l.id)?.name">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!lines.length"><td colspan="4" class="px-5 py-12 text-center text-zinc-500">Search above to add products.</td></tr>
                </tbody>
            </table>
            @error('items')<p class="px-5 py-3 text-sm text-primary-600 border-t border-zinc-100">{{ $message }}</p>@enderror
        </section>

        <aside class="card p-5 space-y-5">
            <div>
                <label for="template" class="field-label">Label paper</label>
                <select id="template" name="template" class="input">
                    @foreach ($templates as $key => $label)
                        <option value="{{ $key }}" @selected(old('template', 'a4_40') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="skip" class="field-label">Skip spots on the sheet</label>
                <input id="skip" type="number" name="skip" min="0" max="100" value="{{ old('skip', 0) }}" class="input" style="max-width:8rem">
                <p class="text-xs text-zinc-500 mt-1">To reuse a part-used sheet, skip the labels already peeled off.</p>
            </div>
            <fieldset class="space-y-2">
                <legend class="field-label">Show on each label</legend>
                @foreach (['show_name' => 'Product name', 'show_price' => 'Price', 'show_shop' => 'Shop name'] as $name => $label)
                    <label class="flex items-center gap-2 text-sm text-zinc-700">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $name !== 'show_shop')) class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500">
                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>
            <div class="border-t border-zinc-100 pt-4">
                <p class="text-sm text-zinc-600 mb-3"><span class="font-display text-2xl text-zinc-900 tabular-nums" x-text="total"></span> labels</p>
                <p x-show="noSku" x-cloak class="text-xs text-primary-600 mb-3" x-text="noSku + ' product(s) have no barcode yet. Use the button at the top first.'"></p>
                <button class="btn-primary w-full" :disabled="!lines.length || noSku > 0">Preview &amp; print</button>
                <p class="text-xs text-zinc-500 mt-2">Opens in a new tab. In the print dialog set margins to "None" and scale to 100%.</p>
            </div>
        </aside>
    </form>
</x-layouts.admin>
