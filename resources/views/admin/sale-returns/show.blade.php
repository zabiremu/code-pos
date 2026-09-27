@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin :title="$return->return_no">
    <x-back-link :href="route('admin.sale-returns.index')">All returns</x-back-link>

    <p class="text-sm text-zinc-500 mb-6">
        {{ $return->return_date->format('d M Y') }} from receipt <a href="{{ route('pos.register.receipt', $return->bill_id) }}" class="text-primary-600 hover:underline underline-offset-4">#{{ $return->bill_id }}</a>
        &middot; {{ $return->customer?->name ?? 'Walk-in customer' }}{{ $return->creator ? ', by '.$return->creator->name : '' }}
    </p>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-3 font-medium">Product</th>
                        <th class="px-5 py-3 font-medium text-right">Qty</th>
                        <th class="px-5 py-3 font-medium text-right">Refund each</th>
                        <th class="px-5 py-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($return->items as $item)
                        <tr>
                            <td class="px-5 py-3">{{ $item->product?->name }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $item->quantity }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->unit_refund) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-zinc-200 font-semibold">
                        <td class="px-5 py-3" colspan="3">{{ $return->refundLabel() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($return->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-3">
                <div><p class="text-zinc-500">Stock</p><p class="font-medium">{{ $return->restock ? 'Put back on the shelf' : 'Not restocked (damaged)' }}</p></div>
                @if ($return->reason)<div><p class="text-zinc-500">Reason</p><p style="white-space:pre-line">{{ $return->reason }}</p></div>@endif
            </section>
            <section class="card p-5">
                <h2 class="text-sm font-semibold">Delete this return</h2>
                <p class="text-sm text-zinc-500 mt-0.5 mb-3">Reverses the stock and the refund on the receipt.</p>
                <x-delete-button variant="danger" label="Delete return" :action="route('admin.sale-returns.destroy', $return)" :confirm="'Delete '.$return->return_no.'?'" />
            </section>
        </aside>
    </div>
</x-layouts.admin>
