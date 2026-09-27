@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin :title="$receipt->receipt_no">
    <x-back-link :href="route('admin.customers.show', $receipt->customer_id)">{{ $receipt->customer?->name }}</x-back-link>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="card-body">
                <p class="text-sm text-zinc-500">Collected {{ $receipt->receipt_date->format('d M Y') }} by {{ $receipt->methodLabel() }}{{ $receipt->creator ? ', by '.$receipt->creator->name : '' }}</p>
                <p class="font-display text-4xl tabular-nums mt-1">{{ $money($receipt->amount) }}</p>
                @if ($receipt->reference)<p class="text-sm text-zinc-600 mt-1">Ref {{ $receipt->reference }}</p>@endif
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                        <th class="px-5 py-2.5 font-medium">Applied to bill</th>
                        <th class="px-5 py-2.5 font-medium text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($receipt->payments as $p)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('pos.register.receipt', $p->bill_id) }}" class="hover:text-primary-600">#{{ $p->bill_id }}</a> <span class="text-zinc-500">&middot; {{ $p->bill?->created_at->format('d M Y') }}</span></td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $money($p->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <aside class="space-y-6">
            @if ($receipt->notes)<section class="card p-5 text-sm"><p class="text-zinc-500">Notes</p><p style="white-space:pre-line">{{ $receipt->notes }}</p></section>@endif
            <section class="card p-5">
                <h2 class="text-sm font-semibold">Delete this collection</h2>
                <p class="text-sm text-zinc-500 mt-0.5 mb-3">The amount goes back onto what {{ $receipt->customer?->name }} owes.</p>
                <x-delete-button variant="danger" label="Delete collection" :action="route('admin.customer-receipts.destroy', $receipt)" :confirm="'Delete '.$receipt->receipt_no.'?'" />
            </section>
        </aside>
    </div>
</x-layouts.admin>
