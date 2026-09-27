@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Stock value">
    <x-report-nav />

    <form method="GET" class="flex flex-wrap items-center gap-2 mb-5 print:hidden">
        <select name="warehouse" class="input w-auto" aria-label="Warehouse" onchange="this.form.submit()">
            <option value="">All warehouses</option>
            @foreach ($warehouses as $w)
                <option value="{{ $w->id }}" @selected($warehouseId === $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
        <button type="button" onclick="window.print()" class="btn-secondary">Print</button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat label="Stock value at cost" :value="$money($cost)" hint="What you paid for what's on hand" />
        <x-stat label="Value at sale price" :value="$money($retail)" hint="If everything sold at today's prices" />
        <x-stat label="Potential profit" :value="$money($retail - $cost)" :tone="$retail - $cost >= 0 ? 'good' : 'bad'" />
    </div>

    @if ($noCost > 0)
        <p class="rounded-xl bg-primary-50 ring-1 ring-primary-600/15 px-5 py-3 text-sm text-primary-900 mb-6">{{ $noCost }} {{ \Illuminate\Support\Str::plural('product', $noCost) }} in stock {{ $noCost === 1 ? 'has' : 'have' }} no purchase price, so {{ $noCost === 1 ? 'it counts' : 'they count' }} as worth nothing at cost.</p>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium text-right">In stock</th>
                            <th class="px-5 py-3 font-medium text-right">Cost each</th>
                            <th class="px-5 py-3 font-medium text-right">Value at cost</th>
                            <th class="px-5 py-3 font-medium text-right">At sale price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($rows as $r)
                            <tr>
                                <td class="px-5 py-2.5"><a href="{{ route('admin.products.edit', $r['product']) }}" class="hover:text-primary-600">{{ $r['product']->name }}</a></td>
                                <td class="px-5 py-2.5 text-right {{ $r['qty'] < 0 ? 'text-primary-600' : '' }}"><x-qty :value="$r['qty']" :unit="$r['product']->unit?->short_name" /></td>
                                <td class="px-5 py-2.5 text-right tabular-nums {{ (float) $r['product']->purchase_price <= 0 ? 'text-primary-600' : 'text-zinc-600' }}">{{ $money($r['product']->purchase_price) }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums font-medium">{{ $money($r['cost']) }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums text-zinc-600">{{ $money($r['retail']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No stock on hand.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @if ($byCategory->isNotEmpty())
            <aside class="card p-5 text-sm" aria-labelledby="cat-heading">
                <h2 id="cat-heading" class="font-display text-xl mb-3">By category</h2>
                <ul class="space-y-3">
                    @foreach ($byCategory as $name => $value)
                        <li>
                            <div class="flex justify-between gap-3"><span>{{ $name }}</span><span class="tabular-nums">{{ $money($value) }}</span></div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-primary-100 overflow-hidden" aria-hidden="true">
                                <div class="h-full bg-primary-600 rounded-full" style="width: {{ $cost > 0 ? max($value / $cost * 100, 2) : 0 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</x-layouts.admin>
