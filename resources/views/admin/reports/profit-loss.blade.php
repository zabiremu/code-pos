@php
    $money = fn ($v) => number_format((float) $v, 2);
    $signed = fn ($v) => ($v < 0 ? '−' : '').number_format(abs((float) $v), 2);
    $pct = fn ($v) => $v === null ? '' : number_format($v, 1).'%';
@endphp
<x-layouts.admin title="Profit & loss">
    <x-report-nav :range="$range" />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2" aria-labelledby="pl-heading">
            <h2 id="pl-heading" class="sr-only">Profit and loss statement</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-zinc-100">
                    <tr><td class="px-5 py-3">Sales <span class="text-zinc-400">(before tax)</span></td><td class="px-5 py-3 text-right tabular-nums">{{ $money($sales) }}</td></tr>
                    <tr><td class="px-5 py-3 text-zinc-600">Less customer returns</td><td class="px-5 py-3 text-right tabular-nums text-zinc-600">−{{ $money($returns) }}</td></tr>
                    <tr class="bg-zinc-50/70 font-semibold"><td class="px-5 py-3">Net sales</td><td class="px-5 py-3 text-right tabular-nums">{{ $money($netSales) }}</td></tr>

                    <tr><td class="px-5 py-3 text-zinc-600">Cost of goods sold</td><td class="px-5 py-3 text-right tabular-nums text-zinc-600">−{{ $money($cogs) }}</td></tr>
                    @if ($returnedCost > 0)
                        <tr><td class="px-5 py-3 text-zinc-600">Returned goods back in stock</td><td class="px-5 py-3 text-right tabular-nums text-zinc-600">+{{ $money($returnedCost) }}</td></tr>
                    @endif
                    <tr class="bg-zinc-50/70 font-semibold">
                        <td class="px-5 py-3">Gross profit <span class="font-normal text-zinc-500">{{ $pct($grossMargin) }}</span></td>
                        <td class="px-5 py-3 text-right tabular-nums {{ $grossProfit < 0 ? 'text-primary-700' : '' }}">{{ $signed($grossProfit) }}</td>
                    </tr>

                    @forelse ($expenses as $name => $sum)
                        <tr><td class="px-5 py-3 text-zinc-600 pl-8">{{ $name }}</td><td class="px-5 py-3 text-right tabular-nums text-zinc-600">−{{ $money($sum) }}</td></tr>
                    @empty
                        <tr><td class="px-5 py-3 text-zinc-500 pl-8">No expenses recorded</td><td class="px-5 py-3 text-right tabular-nums text-zinc-400">0.00</td></tr>
                    @endforelse
                    @if ($totalExpenses > 0)
                        <tr><td class="px-5 py-3 text-zinc-600">Total expenses</td><td class="px-5 py-3 text-right tabular-nums text-zinc-600">−{{ $money($totalExpenses) }}</td></tr>
                    @endif
                    @if ($stockAdjustments != 0)
                        <tr>
                            <td class="px-5 py-3 text-zinc-600">Stock {{ $stockAdjustments < 0 ? 'written off' : 'found' }} <span class="text-zinc-400">(adjustments at cost)</span></td>
                            <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $stockAdjustments > 0 ? '+' : '' }}{{ $signed($stockAdjustments) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="sidebar-surface text-bone">
                        <td class="px-5 py-5 font-display text-2xl">{{ $netProfit < 0 ? 'Net loss' : 'Net profit' }} <span class="font-sans text-sm text-bone/70">{{ $pct($netMargin) }}</span></td>
                        <td class="px-5 py-5 text-right font-display text-3xl tabular-nums">{{ $money(abs($netProfit)) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-2">
                <h2 class="font-display text-xl mb-1">How this is worked out</h2>
                <p class="text-zinc-600">Sales are counted when rung up, whether paid or on credit. Cost of goods uses what each item cost on the day it was sold.</p>
                <p class="text-zinc-600">Tax collected (<span class="tabular-nums">{{ $money($tax) }}</span>) isn't income, so it's left out.</p>
            </section>
            @if ($missingCost > 0)
                <section class="rounded-xl bg-primary-50 ring-1 ring-primary-600/15 p-5 text-sm text-primary-900">
                    <p class="font-semibold">Profit may be overstated</p>
                    <p class="mt-1">{{ $missingCost }} sold {{ \Illuminate\Support\Str::plural('line', $missingCost) }} in this period had no purchase price, so they count as costing nothing. Set purchase prices on your products, or receive stock with a GRN.</p>
                    <a href="{{ route('admin.products.index') }}" class="inline-block mt-2 font-medium underline underline-offset-4">Go to products</a>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
