@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Due collections">
    <x-list-toolbar intro="Money collected from customers against what they owe." :create-route="route('admin.customer-receipts.create')" create-label="Collect payment"
                    :search="$search" placeholder="Collection no. or customer" :action="route('admin.customer-receipts.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Collection</th>
                    <th class="px-5 py-3 font-medium">Customer</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Method</th>
                    <th class="px-5 py-3 font-medium text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($receipts as $r)
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3"><a href="{{ route('admin.customer-receipts.show', $r) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $r->receipt_no }}</a></td>
                        <td class="px-5 py-3"><a href="{{ route('admin.customers.show', $r->customer_id) }}" class="text-zinc-700 hover:text-primary-600">{{ $r->customer?->name }}</a></td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $r->receipt_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $r->methodLabel() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums font-medium">{{ $money($r->amount) }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" noun="collections" :search="$search" :clear-href="route('admin.customer-receipts.index')" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $receipts->links() }}</div>
</x-layouts.admin>
