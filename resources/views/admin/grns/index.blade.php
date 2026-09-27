@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Goods received">
    <x-list-toolbar intro="Each GRN adds stock to a warehouse. Raise one from a purchase, or directly for goods that arrived without an order."
                    :create-route="route('admin.grns.create')" create-label="Receive goods"
                    :search="$search" placeholder="GRN, invoice or supplier" :action="route('admin.grns.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">GRN</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Received</th>
                    <th class="px-5 py-3 font-medium">Warehouse</th>
                    <th class="px-5 py-3 font-medium">Purchase</th>
                    <th class="px-5 py-3 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($grns as $grn)
                    <tr class="hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.grns.show', $grn) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $grn->grn_no }}</a>
                            @if ($grn->supplier_invoice_no)<div class="text-xs text-zinc-500 mt-0.5">Invoice {{ $grn->supplier_invoice_no }}</div>@endif
                            @if ($grn->returns_count)<div class="text-xs text-primary-600 mt-0.5">{{ $grn->returns_count }} return{{ $grn->returns_count > 1 ? 's' : '' }}</div>@endif
                        </td>
                        <td class="px-5 py-3 text-zinc-700">{{ $grn->supplier?->displayName() }}</td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $grn->received_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $grn->warehouse?->name }}</td>
                        <td class="px-5 py-3">
                            @if ($grn->purchase)
                                <a href="{{ route('admin.purchases.show', $grn->purchase) }}" class="text-zinc-600 hover:text-primary-600">{{ $grn->purchase->reference_no }}</a>
                            @else
                                <span class="text-zinc-400">Direct</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($grn->total) }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="GRNs" :search="$search" :clear-href="route('admin.grns.index')" :create-href="route('admin.grns.create')" create-label="Receive your first delivery" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $grns->links() }}</div>
</x-layouts.admin>
