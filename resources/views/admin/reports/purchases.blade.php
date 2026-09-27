@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Purchases report">
    <x-report-nav :range="$range" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat label="Goods received" :value="$money($received)" :hint="$grnCount.' '.\Illuminate\Support\Str::plural('GRN', $grnCount)" />
        <x-stat label="Returned to suppliers" :value="$money($returned)" />
        <x-stat label="Paid to suppliers" :value="$money($paid)" />
        <x-stat label="Open orders" :value="$money($openOrdersValue)" :hint="$openOrders.' not fully received'" />
    </div>

    <section class="card overflow-hidden" aria-labelledby="sup-heading">
        <div class="px-5 pt-5 pb-3"><h2 id="sup-heading" class="font-display text-xl">By supplier</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-2.5 font-medium">Supplier</th>
                        <th class="px-5 py-2.5 font-medium text-right">Received</th>
                        <th class="px-5 py-2.5 font-medium text-right">Returned</th>
                        <th class="px-5 py-2.5 font-medium text-right">Paid</th>
                        <th class="px-5 py-2.5 font-medium text-right">You owe now</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($bySupplier as $row)
                        <tr>
                            <td class="px-5 py-2.5">
                                @if ($row['supplier'])<a href="{{ route('admin.suppliers.show', $row['supplier']) }}" class="hover:text-primary-600">{{ $row['supplier']->displayName() }}</a>@else — @endif
                            </td>
                            <td class="px-5 py-2.5 text-right tabular-nums">{{ $money($row['received']) }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums text-zinc-600">{{ $money($row['returned']) }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums text-zinc-600">{{ $money($row['paid']) }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums font-medium {{ $row['owedNow'] > 0 ? 'text-primary-700' : 'text-zinc-400' }}">{{ $row['owedNow'] > 0 ? $money($row['owedNow']) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No purchasing activity in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
