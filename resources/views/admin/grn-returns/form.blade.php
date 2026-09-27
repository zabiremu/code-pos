@php
    $isEdit = $return->exists;
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<x-layouts.admin :title="$isEdit ? 'Edit '.$return->return_no : 'Return to supplier'">
    <x-back-link :href="$isEdit ? route('admin.grn-returns.show', $return) : ($grn ? route('admin.grns.show', $grn) : route('admin.grn-returns.index'))">
        {{ $isEdit ? $return->return_no : ($grn ? $grn->grn_no : 'All returns') }}
    </x-back-link>

    @if (! $grn)
        {{-- Step 1: choose the GRN the goods came in on. --}}
        <form method="GET" action="{{ route('admin.grn-returns.create') }}" class="card max-w-2xl">
            <div class="px-5">
                <x-form-row label="From GRN" for="grn_id" :required="true" hint="Only GRNs with items left to return are listed.">
                    <select id="grn_id" name="grn_id" required class="input">
                        <option value="">Choose a GRN</option>
                        @foreach ($recentGrns as $g)
                            <option value="{{ $g->id }}">{{ $g->grn_no }} - {{ $g->supplier?->displayName() }} ({{ $g->received_date->format('d M Y') }})</option>
                        @endforeach
                    </select>
                </x-form-row>
            </div>
            <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
                <button class="btn-primary">Continue</button>
                <a href="{{ route('admin.grn-returns.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    @else
        <form method="POST" action="{{ $isEdit ? route('admin.grn-returns.update', $return) : route('admin.grn-returns.store') }}" class="space-y-6"
              x-data="{ qty: {}, cost: @js($grn->items->mapWithKeys(fn ($i) => [$i->id => (float) $i->unit_cost])),
                        get total() { return Object.entries(this.qty).reduce((s, [id, q]) => s + (Number(q) || 0) * (this.cost[id] || 0), 0) } }">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="grn_id" value="{{ $grn->id }}">

            <section class="card" aria-labelledby="ret-heading">
                <div class="card-body !pb-0">
                    <h2 id="ret-heading" class="text-base font-semibold">Return from {{ $grn->grn_no }}</h2>
                    <p class="text-sm text-zinc-500 mt-0.5">{{ $grn->supplier?->displayName() }} &middot; received {{ $grn->received_date->format('d M Y') }} into {{ $grn->warehouse?->name }}</p>
                </div>
                <div class="px-5">
                    <x-form-row label="Return date" for="return_date" :required="true">
                        <input id="return_date" type="date" name="return_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('return_date', $return->return_date?->format('Y-m-d')) }}" class="input" style="max-width:12rem">
                    </x-form-row>
                    <x-form-row label="Reason" for="reason" hint="e.g. damaged in transit, wrong item, expired">
                        <textarea id="reason" name="reason" rows="2" class="input">{{ old('reason', $return->reason) }}</textarea>
                    </x-form-row>
                </div>
            </section>

            <section class="card overflow-hidden" aria-labelledby="items-heading">
                <div class="px-5 pt-5 pb-3"><h2 id="items-heading" class="text-base font-semibold">Quantities to return</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                                <th class="px-5 py-2.5 font-medium">Product</th>
                                <th class="px-5 py-2.5 font-medium text-right">Received</th>
                                <th class="px-5 py-2.5 font-medium text-right">Can return</th>
                                <th class="px-5 py-2.5 font-medium text-right">Unit cost</th>
                                <th class="px-5 py-2.5 font-medium text-right" style="width:10rem">Return</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($grn->items as $i => $item)
                                @php
                                    $mine = $current[$item->id] ?? 0;
                                    $max = $item->returnableQuantity() + $mine;
                                    $value = old("items.$i.quantity", $mine ?: '');
                                @endphp
                                <tr class="{{ $max <= 0 ? 'text-zinc-400' : '' }}">
                                    <td class="px-5 py-3">{{ $item->product?->name }}</td>
                                    <td class="px-5 py-3 text-right"><x-qty :value="$item->quantity" :unit="$item->product?->unit?->short_name" /></td>
                                    <td class="px-5 py-3 text-right"><x-qty :value="$max" /></td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->unit_cost) }}</td>
                                    <td class="px-5 py-2">
                                        <input type="hidden" name="items[{{ $i }}][grn_item_id]" value="{{ $item->id }}">
                                        <input type="number" name="items[{{ $i }}][quantity]" min="0" max="{{ $max }}" step="{{ ($item->product?->unit?->allow_decimal ?? true) ? '0.001' : '1' }}"
                                               value="{{ $value }}" placeholder="0" @disabled($max <= 0)
                                               x-init="qty[{{ $item->id }}] = $el.value" x-model="qty[{{ $item->id }}]"
                                               class="input text-right" aria-label="Quantity of {{ $item->product?->name }} to return">
                                        @error("items.$i.quantity")<p class="text-xs text-primary-600 mt-1">{{ $message }}</p>@enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-zinc-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div>@error('items')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror</div>
                    <p class="text-base font-semibold">Return value <span class="tabular-nums ml-2" x-text="total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></p>
                </div>
            </section>

            <div class="flex gap-2">
                <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Return and remove stock' }}</button>
                <a href="{{ $isEdit ? route('admin.grn-returns.show', $return) : route('admin.grns.show', $grn) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    @endif
</x-layouts.admin>
