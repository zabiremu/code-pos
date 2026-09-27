<x-layouts.admin title="Stock transfers">
    <x-list-toolbar intro="Moving stock between warehouses. Total stock stays the same." :create-route="route('admin.stock-transfers.create')" create-label="New transfer"
                    :search="$search" placeholder="Transfer number" :action="route('admin.stock-transfers.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Transfer</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">From</th>
                    <th class="px-5 py-3 font-medium">To</th>
                    <th class="px-5 py-3 font-medium text-right">Products</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($transfers as $t)
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3"><a href="{{ route('admin.stock-transfers.show', $t) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $t->transfer_no }}</a></td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $t->transfer_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ $t->fromWarehouse?->name }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ $t->toWarehouse?->name }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $t->items_count }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" noun="transfers" :search="$search" :clear-href="route('admin.stock-transfers.index')" :create-href="route('admin.stock-transfers.create')" create-label="Make your first transfer" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $transfers->links() }}</div>
</x-layouts.admin>
