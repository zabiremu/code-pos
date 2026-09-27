@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Sales returns">
    <x-list-toolbar intro="Goods customers brought back, and how they were refunded." :create-route="route('admin.sale-returns.create')" create-label="New return"
                    :search="$search" placeholder="Return no. or receipt #" :action="route('admin.sale-returns.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Return</th>
                    <th class="px-5 py-3 font-medium">Receipt</th>
                    <th class="px-5 py-3 font-medium">Customer</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Refund</th>
                    <th class="px-5 py-3 font-medium text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($returns as $r)
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3"><a href="{{ route('admin.sale-returns.show', $r) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $r->return_no }}</a></td>
                        <td class="px-5 py-3"><a href="{{ route('pos.register.receipt', $r->bill_id) }}" class="text-zinc-600 hover:text-primary-600">#{{ $r->bill_id }}</a></td>
                        <td class="px-5 py-3 text-zinc-700">{{ $r->customer?->name ?? 'Walk-in' }}</td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $r->return_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $r->refundLabel() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums font-medium">{{ $money($r->total) }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="returns" :search="$search" :clear-href="route('admin.sale-returns.index')" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $returns->links() }}</div>
</x-layouts.admin>
