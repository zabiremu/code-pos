@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Purchases">
    <x-list-toolbar intro="Orders placed with suppliers. Stock arrives when you receive goods against them." :create-route="route('admin.purchases.create')" create-label="New purchase"
                    :search="$search" placeholder="PO number or supplier" :action="route('admin.purchases.index')">
        <x-tab-link :href="route('admin.purchases.index', array_filter(['q' => $search ?: null]))" :active="$status === 'all'" :count="$counts->sum()">All</x-tab-link>
        @foreach (\App\Models\Purchase::STATUSES as $key => $label)
            @if (($counts[$key] ?? 0) > 0 || $status === $key)
                <x-tab-link :href="route('admin.purchases.index', array_filter(['status' => $key, 'q' => $search ?: null]))" :active="$status === $key" :count="$counts[$key] ?? 0">{{ $label }}</x-tab-link>
            @endif
        @endforeach
        <x-slot:hidden>@if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif</x-slot:hidden>
    </x-list-toolbar>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Purchase</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Warehouse</th>
                    <th class="px-5 py-3 font-medium text-right">Total</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($purchases as $purchase)
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3"><a href="{{ route('admin.purchases.show', $purchase) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $purchase->reference_no }}</a></td>
                        <td class="px-5 py-3 text-zinc-700">{{ $purchase->supplier?->displayName() }}</td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $purchase->purchase_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $purchase->warehouse?->name }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($purchase->total) }}</td>
                        <td class="px-5 py-3 text-right"><span class="{{ $purchase->statusBadge() }}">{{ $purchase->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="purchases" :search="$search" :clear-href="route('admin.purchases.index')" :create-href="route('admin.purchases.create')" create-label="Create your first purchase" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $purchases->links() }}</div>
</x-layouts.admin>
