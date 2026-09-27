@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Stock adjustments">
    <x-list-toolbar intro="Write off damaged, expired or lost stock, or correct it after a count." :create-route="route('admin.stock-adjustments.create')" create-label="New adjustment"
                    :search="$search" placeholder="Adjustment number" :action="route('admin.stock-adjustments.index')">
        <x-tab-link :href="route('admin.stock-adjustments.index')" :active="! $reason">All</x-tab-link>
        @foreach (\App\Models\StockAdjustment::REASONS as $key => $label)
            <x-tab-link :href="route('admin.stock-adjustments.index', ['reason' => $key])" :active="$reason === $key">{{ $label }}</x-tab-link>
        @endforeach
    </x-list-toolbar>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Adjustment</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Warehouse</th>
                    <th class="px-5 py-3 font-medium">Reason</th>
                    <th class="px-5 py-3 font-medium text-right">Products</th>
                    <th class="px-5 py-3 font-medium text-right">Value at cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($adjustments as $a)
                    @php $value = $a->value(); @endphp
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3"><a href="{{ route('admin.stock-adjustments.show', $a) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $a->adjustment_no }}</a></td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $a->adjustment_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-700">{{ $a->warehouse?->name }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $a->reasonLabel() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $a->items->count() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ $value < 0 ? 'text-primary-600' : 'text-emerald-700' }}">{{ $value > 0 ? '+' : '' }}{{ $money($value) }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="adjustments" :search="$search" :clear-href="route('admin.stock-adjustments.index')" :create-href="route('admin.stock-adjustments.create')" create-label="Make your first adjustment" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $adjustments->links() }}</div>
</x-layouts.admin>
