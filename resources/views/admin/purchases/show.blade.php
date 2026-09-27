@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin :title="$purchase->reference_no">
    <x-back-link :href="route('admin.purchases.index')">All purchases</x-back-link>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <span class="{{ $purchase->statusBadge() }}">{{ $purchase->statusLabel() }}</span>
            <span class="text-sm text-zinc-500">Ordered {{ $purchase->purchase_date->format('d M Y') }}{{ $purchase->creator ? ' by '.$purchase->creator->name : '' }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($purchase->canReceive())
                <a href="{{ route('admin.grns.create', ['purchase_id' => $purchase->id]) }}" class="btn-primary">Receive goods</a>
            @endif
            @if ($purchase->isEditable())
                <a href="{{ route('admin.purchases.edit', $purchase) }}" class="btn-secondary">Edit</a>
            @endif
            @if (in_array($purchase->status, ['draft', 'ordered'], true) && $purchase->grns->isEmpty())
                <form method="POST" action="{{ route('admin.purchases.cancel', $purchase) }}" onsubmit="return confirm('Cancel {{ $purchase->reference_no }}?')">
                    @csrf @method('PATCH')
                    <button class="btn-secondary">Cancel order</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2" aria-labelledby="items-heading">
            <div class="px-5 pt-5 pb-3"><h2 id="items-heading" class="font-display text-xl">Items</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-2.5 font-medium">Product</th>
                            <th class="px-5 py-2.5 font-medium text-right">Ordered</th>
                            <th class="px-5 py-2.5 font-medium text-right">Received</th>
                            <th class="px-5 py-2.5 font-medium text-right">Unit cost</th>
                            <th class="px-5 py-2.5 font-medium text-right">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($purchase->items as $item)
                            @php $done = (float) $item->received_quantity >= (float) $item->quantity; @endphp
                            <tr>
                                <td class="px-5 py-3">{{ $item->product?->name }}</td>
                                <td class="px-5 py-3 text-right"><x-qty :value="$item->quantity" :unit="$item->product?->unit?->short_name" /></td>
                                <td class="px-5 py-3 text-right {{ $done ? 'text-emerald-700' : ((float) $item->received_quantity > 0 ? 'text-zinc-900' : 'text-zinc-400') }}"><x-qty :value="$item->received_quantity" /></td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->unit_cost) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-5 py-4 flex justify-end">
                <dl class="w-full max-w-xs text-sm space-y-1.5">
                    <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd class="tabular-nums">{{ $money($purchase->subtotal) }}</dd></div>
                    @if ($purchase->discount > 0)<div class="flex justify-between"><dt class="text-zinc-500">Discount</dt><dd class="tabular-nums">−{{ $money($purchase->discount) }}</dd></div>@endif
                    @if ($purchase->tax > 0)<div class="flex justify-between"><dt class="text-zinc-500">Tax</dt><dd class="tabular-nums">{{ $money($purchase->tax) }}</dd></div>@endif
                    @if ($purchase->shipping > 0)<div class="flex justify-between"><dt class="text-zinc-500">Shipping</dt><dd class="tabular-nums">{{ $money($purchase->shipping) }}</dd></div>@endif
                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ $money($purchase->total) }}</dd></div>
                </dl>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-3" aria-label="Order details">
                <div><p class="text-zinc-500">Supplier</p><p class="font-medium">{{ $purchase->supplier?->displayName() }}</p>
                    @if ($purchase->supplier?->phone)<p class="text-zinc-600">{{ $purchase->supplier->phone }}</p>@endif</div>
                <div><p class="text-zinc-500">Deliver to</p><p class="font-medium">{{ $purchase->warehouse?->name }}</p></div>
                @if ($purchase->expected_date)<div><p class="text-zinc-500">Expected by</p><p class="font-medium">{{ $purchase->expected_date->format('d M Y') }}</p></div>@endif
                @if ($purchase->notes)<div><p class="text-zinc-500">Notes</p><p style="white-space:pre-line">{{ $purchase->notes }}</p></div>@endif
            </section>

            <section class="card overflow-hidden" aria-labelledby="grn-heading">
                <div class="px-5 pt-5 pb-3"><h2 id="grn-heading" class="font-display text-xl">Goods received</h2></div>
                <ul class="divide-y divide-zinc-100 border-t border-zinc-100 text-sm">
                    @forelse ($purchase->grns as $grn)
                        <li class="px-5 py-2.5 flex justify-between gap-3">
                            <a href="{{ route('admin.grns.show', $grn) }}" class="font-medium hover:text-primary-600">{{ $grn->grn_no }}</a>
                            <span class="text-zinc-500">{{ $grn->received_date->format('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-zinc-500">Nothing received yet.</li>
                    @endforelse
                </ul>
            </section>

            @if ($purchase->grns->isEmpty())
                <section class="card p-5">
                    <h2 class="text-sm font-semibold">Delete this purchase</h2>
                    <p class="text-sm text-zinc-500 mt-0.5 mb-3">Nothing has been received, so no stock is affected.</p>
                    <x-delete-button variant="danger" label="Delete purchase" :action="route('admin.purchases.destroy', $purchase)" :confirm="'Delete '.$purchase->reference_no.'?'" />
                </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
