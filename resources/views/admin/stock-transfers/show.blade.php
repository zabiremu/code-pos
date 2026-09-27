<x-layouts.admin :title="$transfer->transfer_no">
    <x-back-link :href="route('admin.stock-transfers.index')">All transfers</x-back-link>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-zinc-500">
            {{ $transfer->transfer_date->format('d M Y') }}:
            <span class="font-medium text-zinc-800">{{ $transfer->fromWarehouse?->name }}</span> &rarr;
            <span class="font-medium text-zinc-800">{{ $transfer->toWarehouse?->name }}</span>{{ $transfer->creator ? ', by '.$transfer->creator->name : '' }}
        </p>
        <a href="{{ route('admin.stock-transfers.edit', $transfer) }}" class="btn-secondary">Edit</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-3 font-medium">Product</th>
                        <th class="px-5 py-3 font-medium text-right">Moved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($transfer->items as $item)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('admin.products.edit', $item->product_id) }}" class="hover:text-primary-600">{{ $item->product?->name }}</a></td>
                            <td class="px-5 py-3 text-right"><x-qty :value="$item->quantity" :unit="$item->product?->unit?->short_name" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <aside class="space-y-6">
            @if ($transfer->notes)
                <section class="card p-5 text-sm"><p class="text-zinc-500">Notes</p><p style="white-space:pre-line">{{ $transfer->notes }}</p></section>
            @endif
            <section class="card p-5">
                <h2 class="text-sm font-semibold">Delete this transfer</h2>
                <p class="text-sm text-zinc-500 mt-0.5 mb-3">Moves the stock back to {{ $transfer->fromWarehouse?->name }}. Blocked if it has already been sold from {{ $transfer->toWarehouse?->name }}.</p>
                <x-delete-button variant="danger" label="Delete transfer" :action="route('admin.stock-transfers.destroy', $transfer)" :confirm="'Delete '.$transfer->transfer_no.' and move the stock back?'" />
            </section>
        </aside>
    </div>
</x-layouts.admin>
