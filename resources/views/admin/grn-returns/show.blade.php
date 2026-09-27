@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin :title="$return->return_no">
    <x-back-link :href="route('admin.grn-returns.index')">All returns</x-back-link>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-zinc-500">
            Returned {{ $return->return_date->format('d M Y') }} from
            <a href="{{ route('admin.grns.show', $return->grn) }}" class="text-primary-600 hover:underline underline-offset-4">{{ $return->grn->grn_no }}</a>
            to {{ $return->grn->supplier?->displayName() }}{{ $return->creator ? ', by '.$return->creator->name : '' }}
        </p>
        <a href="{{ route('admin.grn-returns.edit', $return) }}" class="btn-secondary">Edit</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2" aria-labelledby="items-heading">
            <div class="px-5 pt-5 pb-3"><h2 id="items-heading" class="font-display text-xl">Items returned</h2></div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-2.5 font-medium">Product</th>
                        <th class="px-5 py-2.5 font-medium text-right">Quantity</th>
                        <th class="px-5 py-2.5 font-medium text-right">Unit cost</th>
                        <th class="px-5 py-2.5 font-medium text-right">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($return->items as $item)
                        <tr>
                            <td class="px-5 py-3">{{ $item->product?->name }}</td>
                            <td class="px-5 py-3 text-right"><x-qty :value="$item->quantity" :unit="$item->product?->unit?->short_name" /></td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->unit_cost) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-zinc-200 font-semibold">
                        <td class="px-5 py-3" colspan="3">Total</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($return->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-3">
                <div><p class="text-zinc-500">Taken out of</p><p class="font-medium">{{ $return->grn->warehouse?->name }}</p></div>
                @if ($return->reason)<div><p class="text-zinc-500">Reason</p><p style="white-space:pre-line">{{ $return->reason }}</p></div>@endif
            </section>
            <section class="card p-5">
                <h2 class="text-sm font-semibold">Delete this return</h2>
                <p class="text-sm text-zinc-500 mt-0.5 mb-3">Puts the returned quantities back into stock.</p>
                <x-delete-button variant="danger" label="Delete return" :action="route('admin.grn-returns.destroy', $return)" :confirm="'Delete '.$return->return_no.' and put the stock back?'" />
            </section>
        </aside>
    </div>
</x-layouts.admin>
