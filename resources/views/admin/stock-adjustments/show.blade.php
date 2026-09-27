@php $money = fn ($v) => number_format((float) $v, 2); $value = $adjustment->value(); @endphp
<x-layouts.admin :title="$adjustment->adjustment_no">
    <x-back-link :href="route('admin.stock-adjustments.index')">All adjustments</x-back-link>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-zinc-500">{{ $adjustment->reasonLabel() }} in {{ $adjustment->warehouse?->name }}, {{ $adjustment->adjustment_date->format('d M Y') }}{{ $adjustment->creator ? ', by '.$adjustment->creator->name : '' }}</p>
        @if ($adjustment->reason !== 'count')
            <a href="{{ route('admin.stock-adjustments.edit', $adjustment) }}" class="btn-secondary">Edit</a>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-3 font-medium">Product</th>
                        <th class="px-5 py-3 font-medium text-right">Change</th>
                        <th class="px-5 py-3 font-medium text-right">Cost</th>
                        <th class="px-5 py-3 font-medium text-right">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($adjustment->items as $item)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('admin.products.edit', $item->product_id) }}" class="hover:text-primary-600">{{ $item->product?->name }}</a></td>
                            <td class="px-5 py-3 text-right font-medium {{ $item->quantity < 0 ? 'text-primary-600' : 'text-emerald-700' }}">{{ $item->quantity > 0 ? '+' : '' }}<x-qty :value="$item->quantity" :unit="$item->product?->unit?->short_name" /></td>
                            <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $money($item->unit_cost) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->quantity * $item->unit_cost) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-zinc-200 font-semibold">
                        <td class="px-5 py-3" colspan="3">{{ $value < 0 ? 'Written off at cost' : 'Added at cost' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ $value < 0 ? 'text-primary-600' : 'text-emerald-700' }}">{{ $money($value) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <aside class="space-y-6">
            @if ($adjustment->notes)
                <section class="card p-5 text-sm"><p class="text-zinc-500">Notes</p><p style="white-space:pre-line">{{ $adjustment->notes }}</p></section>
            @endif
            <section class="card p-5">
                <h2 class="text-sm font-semibold">Delete this adjustment</h2>
                <p class="text-sm text-zinc-500 mt-0.5 mb-3">Reverses every change it made to stock.</p>
                <x-delete-button variant="danger" label="Delete adjustment" :action="route('admin.stock-adjustments.destroy', $adjustment)" :confirm="'Delete '.$adjustment->adjustment_no.' and reverse it?'" />
            </section>
        </aside>
    </div>
</x-layouts.admin>
