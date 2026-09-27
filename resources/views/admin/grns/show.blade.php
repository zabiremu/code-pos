@php
    $money = fn ($v) => number_format((float) $v, 2);
    $editable = $grn->returns->isEmpty() && $grn->payments->isEmpty();
    $returned = (float) $grn->returns->sum('total');
    $paid = (float) $grn->payments->sum('amount');
    $due = round((float) $grn->total - $returned - $paid, 2);
@endphp
<x-layouts.admin :title="$grn->grn_no">
    <x-back-link :href="route('admin.grns.index')">All GRNs</x-back-link>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-zinc-500">Received {{ $grn->received_date->format('d M Y') }} into {{ $grn->warehouse?->name }}{{ $grn->creator ? ' by '.$grn->creator->name : '' }}</p>
        <div class="flex flex-wrap gap-2">
            @if ($due > 0)
                <a href="{{ route('admin.supplier-payments.create', ['grn_id' => $grn->id]) }}" class="btn-primary">Pay {{ $money($due) }}</a>
            @endif
            @if ($grn->hasReturnableItems())
                <a href="{{ route('admin.grn-returns.create', ['grn_id' => $grn->id]) }}" class="btn-secondary">Return items</a>
            @endif
            @if ($editable)
                <a href="{{ route('admin.grns.edit', $grn) }}" class="btn-secondary">Edit</a>
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
                            <th class="px-5 py-2.5 font-medium text-right">Received</th>
                            <th class="px-5 py-2.5 font-medium text-right">Returned</th>
                            <th class="px-5 py-2.5 font-medium text-right">Unit cost</th>
                            <th class="px-5 py-2.5 font-medium text-right">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($grn->items as $item)
                            <tr>
                                <td class="px-5 py-3"><a href="{{ route('admin.products.edit', $item->product_id) }}" class="hover:text-primary-600">{{ $item->product?->name }}</a></td>
                                <td class="px-5 py-3 text-right"><x-qty :value="$item->quantity" :unit="$item->product?->unit?->short_name" /></td>
                                <td class="px-5 py-3 text-right {{ $item->returned_quantity > 0 ? 'text-primary-600' : 'text-zinc-400' }}"><x-qty :value="$item->returned_quantity" /></td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->unit_cost) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ $money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-zinc-200 font-semibold">
                            <td class="px-5 py-3" colspan="4">Total</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($grn->total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-3" aria-label="Delivery details">
                <div><p class="text-zinc-500">Supplier</p><a href="{{ route('admin.suppliers.show', $grn->supplier_id) }}" class="font-medium hover:text-primary-600">{{ $grn->supplier?->displayName() }}</a></div>
                <div><p class="text-zinc-500">Purchase</p>
                    @if ($grn->purchase)
                        <a href="{{ route('admin.purchases.show', $grn->purchase) }}" class="font-medium text-primary-600 hover:underline underline-offset-4">{{ $grn->purchase->reference_no }}</a>
                    @else
                        <p class="font-medium">Direct (no purchase order)</p>
                    @endif
                </div>
                @if ($grn->supplier_invoice_no)<div><p class="text-zinc-500">Supplier invoice</p><p class="font-medium">{{ $grn->supplier_invoice_no }}</p></div>@endif
                @if ($grn->notes)<div><p class="text-zinc-500">Notes</p><p style="white-space:pre-line">{{ $grn->notes }}</p></div>@endif
            </section>

            <section class="card p-5 text-sm" aria-labelledby="pay-heading">
                <h2 id="pay-heading" class="font-display text-xl mb-3">Payment</h2>
                <dl class="space-y-1.5">
                    <div class="flex justify-between"><dt class="text-zinc-500">GRN total</dt><dd class="tabular-nums">{{ $money($grn->total) }}</dd></div>
                    @if ($returned > 0)<div class="flex justify-between"><dt class="text-zinc-500">Returned</dt><dd class="tabular-nums">−{{ $money($returned) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-zinc-500">Paid</dt><dd class="tabular-nums">−{{ $money($paid) }}</dd></div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold"><dt>Due</dt>
                        <dd class="tabular-nums {{ $due > 0 ? 'text-primary-700' : 'text-emerald-700' }}">{{ $due > 0 ? $money($due) : 'Paid in full' }}</dd></div>
                </dl>
                @if ($grn->payments->isNotEmpty())
                    <ul class="mt-4 pt-3 border-t border-zinc-100 space-y-1.5">
                        @foreach ($grn->payments as $p)
                            <li class="flex justify-between gap-3">
                                <a href="{{ route('admin.supplier-payments.edit', $p) }}" class="hover:text-primary-600">{{ $p->payment_no }} <span class="text-zinc-500">&middot; {{ $p->payment_date->format('d M') }}</span></a>
                                <span class="tabular-nums">{{ $money($p->amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card overflow-hidden" aria-labelledby="returns-heading">
                <div class="px-5 pt-5 pb-3"><h2 id="returns-heading" class="font-display text-xl">Returns</h2></div>
                <ul class="divide-y divide-zinc-100 border-t border-zinc-100 text-sm">
                    @forelse ($grn->returns as $return)
                        <li class="px-5 py-2.5 flex justify-between gap-3">
                            <a href="{{ route('admin.grn-returns.show', $return) }}" class="font-medium hover:text-primary-600">{{ $return->return_no }}</a>
                            <span class="tabular-nums text-zinc-600">{{ $money($return->total) }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-zinc-500">Nothing returned from this GRN.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-semibold">Delete this GRN</h2>
                @if ($editable)
                    <p class="text-sm text-zinc-500 mt-0.5 mb-3">Removes its stock from {{ $grn->warehouse?->name }}. Blocked if that stock has already been sold.</p>
                    <x-delete-button variant="danger" label="Delete GRN" :action="route('admin.grns.destroy', $grn)" :confirm="'Delete '.$grn->grn_no.' and remove its stock?'" />
                @else
                    <p class="text-sm text-zinc-500 mt-0.5">Delete its returns and payments first.</p>
                @endif
            </section>
        </aside>
    </div>
</x-layouts.admin>
