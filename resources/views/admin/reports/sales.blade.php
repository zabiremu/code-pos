@php
    $money = fn ($v) => number_format((float) $v, 2);
    $methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'mobile_wallet' => 'Mobile pay', 'other' => 'Other'];
    $peak = max(max($daily ?: [0]), 1);
@endphp
<x-layouts.admin title="Sales report">
    <x-report-nav :range="$range" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat label="Sales" :value="$money($gross)" :hint="$count.' '.\Illuminate\Support\Str::plural('receipt', $count)" />
        <x-stat label="Average sale" :value="$money($average)" />
        <x-stat label="Returns" :value="$money($returns)" :tone="$returns > 0 ? 'bad' : null" />
        <x-stat label="Sold on credit" :value="$money($onCredit)" hint="Still owed on these sales" :tone="$onCredit > 0 ? 'bad' : null" />
    </div>

    @if (count($daily) > 1)
        <section class="card p-5 mb-6" aria-labelledby="daily-heading">
            <h2 id="daily-heading" class="font-display text-xl mb-4">By day</h2>
            <div class="flex items-end gap-[2px] h-40" role="img" aria-label="Sales by day">
                @foreach ($daily as $day => $amount)
                    <div class="flex-1 min-w-0 group relative flex flex-col justify-end h-full">
                        <div class="rounded-t bg-primary-600/80 group-hover:bg-primary-600 transition-colors" style="height: {{ $amount > 0 ? max($amount / $peak * 100, 2) : 0 }}%"></div>
                        <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block whitespace-nowrap rounded bg-zinc-900 text-white text-xs px-2 py-1 z-10">
                            {{ \Illuminate\Support\Carbon::parse($day)->format('d M') }}: {{ $money($amount) }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-zinc-400 mt-2">
                <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($daily))->format('d M') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse(array_key_last($daily))->format('d M') }}</span>
            </div>
        </section>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2" aria-labelledby="top-heading">
            <div class="px-5 pt-5 pb-3"><h2 id="top-heading" class="font-display text-xl">Best sellers</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-2.5 font-medium">Product</th>
                            <th class="px-5 py-2.5 font-medium text-right">Qty</th>
                            <th class="px-5 py-2.5 font-medium text-right">Sales</th>
                            <th class="px-5 py-2.5 font-medium text-right">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($topProducts as $row)
                            @php $profit = (float) $row->revenue - (float) $row->cost; @endphp
                            <tr>
                                <td class="px-5 py-2.5"><a href="{{ route('admin.products.edit', $row->product_id) }}" class="hover:text-primary-600">{{ $row->name }}</a></td>
                                <td class="px-5 py-2.5 text-right tabular-nums">{{ (int) $row->qty }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums">{{ $money($row->revenue) }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums {{ $profit < 0 ? 'text-primary-600' : 'text-emerald-700' }}">{{ (float) $row->cost > 0 ? $money($profit) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">No sales in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="card p-5 text-sm" aria-labelledby="method-heading">
                <h2 id="method-heading" class="font-display text-xl mb-3">Money taken</h2>
                <dl class="space-y-2">
                    @forelse ($byMethod as $method => $sum)
                        <div class="flex justify-between"><dt class="text-zinc-600">{{ $methodLabels[$method] ?? ucfirst($method) }}</dt><dd class="tabular-nums">{{ $money($sum) }}</dd></div>
                    @empty
                        <p class="text-zinc-500">No payments in this period.</p>
                    @endforelse
                    @if ($byMethod->isNotEmpty())
                        <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ $money($byMethod->sum()) }}</dd></div>
                    @endif
                </dl>
                <p class="text-xs text-zinc-500 mt-3">Includes due collected from customers in this period.</p>
            </section>
            <section class="card p-5 text-sm" aria-labelledby="cashier-heading">
                <h2 id="cashier-heading" class="font-display text-xl mb-3">By cashier</h2>
                <dl class="space-y-2">
                    @forelse ($byCashier as $name => $row)
                        <div class="flex justify-between gap-3"><dt class="text-zinc-600">{{ $name }} <span class="text-zinc-400">&middot; {{ $row['count'] }}</span></dt><dd class="tabular-nums">{{ $money($row['total']) }}</dd></div>
                    @empty
                        <p class="text-zinc-500">No sales in this period.</p>
                    @endforelse
                </dl>
            </section>
            <section class="card p-5 text-sm space-y-1.5">
                <div class="flex justify-between"><span class="text-zinc-500">Tax collected</span><span class="tabular-nums">{{ $money($tax) }}</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">Discounts given</span><span class="tabular-nums">{{ $money($discount) }}</span></div>
            </section>
        </aside>
    </div>
</x-layouts.admin>
