@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Supplier payments">
    <x-list-toolbar intro="Money paid to suppliers, against a GRN or as an advance." :create-route="route('admin.supplier-payments.create')" create-label="Record payment"
                    :search="$search" placeholder="Payment no., reference or supplier" :action="route('admin.supplier-payments.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Payment</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Method</th>
                    <th class="px-5 py-3 font-medium">For</th>
                    <th class="px-5 py-3 font-medium text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($payments as $p)
                    <tr class="group hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.supplier-payments.edit', $p) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $p->payment_no }}</a>
                            @if ($p->reference)<div class="text-xs text-zinc-500 mt-0.5">Ref {{ $p->reference }}</div>@endif
                        </td>
                        <td class="px-5 py-3"><a href="{{ route('admin.suppliers.show', $p->supplier_id) }}" class="text-zinc-700 hover:text-primary-600">{{ $p->supplier?->displayName() }}</a></td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $p->payment_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $p->methodLabel() }}</td>
                        <td class="px-5 py-3">
                            @if ($p->grn)<a href="{{ route('admin.grns.show', $p->grn) }}" class="text-zinc-600 hover:text-primary-600">{{ $p->grn->grn_no }}</a>@else<span class="text-zinc-400">On account</span>@endif
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums font-medium">{{ $money($p->amount) }}</td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="payments" :search="$search" :clear-href="route('admin.supplier-payments.index')" :create-href="route('admin.supplier-payments.create')" create-label="Record your first payment" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $payments->links() }}</div>
</x-layouts.admin>
