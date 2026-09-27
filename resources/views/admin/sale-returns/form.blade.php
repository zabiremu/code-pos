@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Return items">
    <x-back-link :href="route('admin.sale-returns.index')">All returns</x-back-link>

    @if (! $bill)
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <form method="GET" action="{{ route('admin.sale-returns.create') }}" class="card xl:col-span-2">
                <div class="px-5">
                    <x-form-row label="Receipt number" for="bill" :required="true" hint="Printed at the top of the receipt, e.g. 128.">
                        <input id="bill" name="bill" value="{{ request('bill') }}" required inputmode="numeric" class="input" style="max-width:12rem" autofocus>
                        @if ($notFound)<p class="text-xs text-primary-600 mt-1.5">No receipt #{{ request('bill') }} found.</p>@endif
                    </x-form-row>
                </div>
                <div class="px-5 py-4 border-t border-zinc-100"><button class="btn-primary">Find receipt</button></div>
            </form>
            <section class="card overflow-hidden" aria-labelledby="recent-heading">
                <div class="px-5 pt-5 pb-3"><h2 id="recent-heading" class="font-display text-xl">Recent receipts</h2></div>
                <ul class="divide-y divide-zinc-100 border-t border-zinc-100 text-sm">
                    @foreach ($recentBills as $b)
                        <li><a href="{{ route('admin.sale-returns.create', ['bill' => $b->id]) }}" class="px-5 py-2.5 flex justify-between gap-3 hover:bg-zinc-50">
                            <span>#{{ $b->id }} <span class="text-zinc-500">&middot; {{ $b->sale?->customer?->name ?? 'Walk-in' }}</span></span>
                            <span class="tabular-nums text-zinc-600">{{ $money($b->grand_total) }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </section>
        </div>
    @else
        @php
            $ratio = (float) $bill->subtotal > 0 ? (float) $bill->grand_total / (float) $bill->subtotal : 0;
            $due = $bill->balanceDue();
        @endphp
        <form method="POST" action="{{ route('admin.sale-returns.store') }}" class="space-y-6"
              x-data="{ qty: {}, unit: @js($bill->sale->items->mapWithKeys(fn ($i) => [$i->id => round((float) $i->unit_price * $ratio, 2)])), method: @js(old('refund_method', $due > 0 ? 'adjust_due' : 'cash')),
                        get total() { return Object.entries(this.qty).reduce((s, [id, q]) => s + (parseInt(q) || 0) * (this.unit[id] || 0), 0) } }">
            @csrf
            <input type="hidden" name="bill_id" value="{{ $bill->id }}">

            <section class="card overflow-hidden" aria-labelledby="items-heading">
                <div class="px-5 pt-5 pb-3 flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="items-heading" class="text-base font-semibold">Receipt #{{ $bill->id }} &middot; {{ $bill->created_at->format('d M Y, g:i A') }}</h2>
                    <p class="text-sm text-zinc-500">{{ $bill->sale->customer?->label() ?? 'Walk-in customer' }} &middot; total {{ $money($bill->grand_total) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                                <th class="px-5 py-2.5 font-medium">Product</th>
                                <th class="px-5 py-2.5 font-medium text-right">Bought</th>
                                <th class="px-5 py-2.5 font-medium text-right">Can return</th>
                                <th class="px-5 py-2.5 font-medium text-right" title="Price including its share of tax and discount">Refund each</th>
                                <th class="px-5 py-2.5 font-medium text-right" style="width:9rem">Return</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($bill->sale->items as $i => $item)
                                @php $max = $item->returnableQuantity(); @endphp
                                <tr class="{{ $max <= 0 ? 'text-zinc-400' : '' }}">
                                    <td class="px-5 py-3">{{ $item->product?->name }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $item->quantity }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $max }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->unit_price * $ratio) }}</td>
                                    <td class="px-5 py-2">
                                        <input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}">
                                        <input type="number" name="items[{{ $i }}][quantity]" min="0" max="{{ $max }}" step="1" placeholder="0" @disabled($max <= 0)
                                               value="{{ old("items.$i.quantity") }}" x-init="qty[{{ $item->id }}] = $el.value" x-model="qty[{{ $item->id }}]"
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
                    <p class="text-base font-semibold">Refund <span class="font-display text-2xl tabular-nums ml-2" x-text="total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></p>
                </div>
            </section>

            <section class="card">
                <div class="px-5">
                    <x-form-row label="Refund as" for="refund_method" :required="true" :hint="$due > 0 ? 'They still owe '.$money($due).' on this receipt.' : null">
                        <select id="refund_method" name="refund_method" x-model="method" class="input" style="max-width:20rem">
                            @foreach (\App\Models\SaleReturn::REFUND_METHODS as $key => $label)
                                @if ($key !== 'adjust_due' || $due > 0)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-checkbox-row label="Stock" name="restock" :checked="true" text="Put the returned items back on the shelf" hint="Untick for damaged goods that can't be sold again." />
                    <x-form-row label="Date" for="return_date" :required="true">
                        <input id="return_date" type="date" name="return_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('return_date', today()->format('Y-m-d')) }}" class="input" style="max-width:12rem">
                    </x-form-row>
                    <x-form-row label="Reason" for="reason">
                        <textarea id="reason" name="reason" rows="2" class="input" placeholder="e.g. wrong size, faulty">{{ old('reason') }}</textarea>
                    </x-form-row>
                </div>
            </section>

            <div class="flex gap-2">
                <button class="btn-primary">Save return</button>
                <a href="{{ route('admin.sale-returns.create') }}" class="btn-secondary">Different receipt</a>
            </div>
        </form>
    @endif
</x-layouts.admin>
