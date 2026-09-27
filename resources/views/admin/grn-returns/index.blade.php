@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Returns to supplier">
    <x-list-toolbar intro="Goods sent back from a GRN. Each return takes that stock out again."
                    :create-route="route('admin.grn-returns.create')" create-label="New return"
                    :search="$search" placeholder="Return or GRN number" :action="route('admin.grn-returns.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Return</th>
                    <th class="px-5 py-3 font-medium">From GRN</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Items</th>
                    <th class="px-5 py-3 font-medium text-right">Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($returns as $return)
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3"><a href="{{ route('admin.grn-returns.show', $return) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $return->return_no }}</a></td>
                        <td class="px-5 py-3"><a href="{{ route('admin.grns.show', $return->grn_id) }}" class="text-zinc-600 hover:text-primary-600">{{ $return->grn?->grn_no }}</a></td>
                        <td class="px-5 py-3 text-zinc-700">{{ $return->grn?->supplier?->displayName() }}</td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $return->return_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $return->items_count }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($return->total) }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="returns" :search="$search" :clear-href="route('admin.grn-returns.index')" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $returns->links() }}</div>
</x-layouts.admin>
