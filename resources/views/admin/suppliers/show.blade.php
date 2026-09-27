@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin :title="$supplier->displayName()">
    <x-back-link :href="route('admin.suppliers.index')">All suppliers</x-back-link>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2 space-y-6">
            {{-- Balance --}}
            <section class="rounded-2xl sidebar-surface text-bone p-6 md:p-8" aria-labelledby="bal-heading">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 id="bal-heading" class="text-sm text-bone/70">{{ $balance < 0 ? 'Advance paid (they owe you)' : 'You owe' }}</h2>
                        <p class="font-display text-5xl leading-none mt-3 tabular-nums">{{ $money(abs($balance)) }}</p>
                    </div>
                    <a href="{{ route('admin.supplier-payments.create', ['supplier_id' => $supplier->id]) }}"
                       class="btn bg-bone text-primary-700 hover:bg-white shadow-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brass">Record payment</a>
                </div>
                <dl class="grid grid-cols-3 gap-4 mt-8 text-sm">
                    <div><dt class="text-bone/60">Goods received</dt><dd class="tabular-nums mt-1 text-base">{{ $money($totals['received']) }}</dd></div>
                    <div><dt class="text-bone/60">Returned</dt><dd class="tabular-nums mt-1 text-base">−{{ $money($totals['returned']) }}</dd></div>
                    <div><dt class="text-bone/60">Paid</dt><dd class="tabular-nums mt-1 text-base">−{{ $money($totals['paid']) }}</dd></div>
                </dl>
            </section>

            {{-- Ledger --}}
            <section class="card overflow-hidden" aria-labelledby="ledger-heading">
                <div class="px-5 pt-5 pb-3 flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="ledger-heading" class="font-display text-xl">Ledger</h2>
                    <p class="text-xs text-zinc-500">Newest first. Balance is what you owe after each entry.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                                <th class="px-5 py-2.5 font-medium">Date</th>
                                <th class="px-5 py-2.5 font-medium">Entry</th>
                                <th class="px-5 py-2.5 font-medium text-right">Owed +</th>
                                <th class="px-5 py-2.5 font-medium text-right">Paid / returned −</th>
                                <th class="px-5 py-2.5 font-medium text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($entries as $e)
                                <tr>
                                    <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">{{ $e['date']->format('d M Y') }}</td>
                                    <td class="px-5 py-3">
                                        <a href="{{ $e['url'] }}" class="font-medium hover:text-primary-600">{{ $e['type'] }} {{ $e['no'] }}</a>
                                        @if ($e['detail'])<div class="text-xs text-zinc-500 mt-0.5">{{ $e['detail'] }}</div>@endif
                                    </td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $e['credit'] ? $money($e['credit']) : '' }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-emerald-700">{{ $e['debit'] ? $money($e['debit']) : '' }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums font-medium">{{ $money($e['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No goods received or payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-3" aria-label="Contact">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-base">{{ $supplier->displayName() }}</p>
                        @if ($supplier->company_name)<p class="text-zinc-500">{{ $supplier->name }}</p>@endif
                    </div>
                    <span class="{{ $supplier->is_active ? 'badge-green' : 'badge-gray' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                @if ($supplier->phone)<p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $supplier->phone) }}" class="hover:text-primary-600">{{ $supplier->phone }}</a></p>@endif
                @if ($supplier->email)<p><a href="mailto:{{ $supplier->email }}" class="hover:text-primary-600">{{ $supplier->email }}</a></p>@endif
                @if ($supplier->tax_number)<p class="text-zinc-600">VAT/BIN {{ $supplier->tax_number }}</p>@endif
                @if ($supplier->address)<p class="text-zinc-600" style="white-space:pre-line">{{ $supplier->address }}</p>@endif
                @if ($supplier->notes)<p class="text-zinc-600 border-t border-zinc-100 pt-3" style="white-space:pre-line">{{ $supplier->notes }}</p>@endif
                <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn-secondary w-full justify-center">Edit details</a>
            </section>
            <section class="card p-5 text-sm space-y-2">
                <a href="{{ route('admin.purchases.create') }}" class="block text-primary-600 font-medium hover:underline underline-offset-4">New purchase order</a>
                <a href="{{ route('admin.grns.create') }}" class="block text-primary-600 font-medium hover:underline underline-offset-4">Receive goods</a>
                <a href="{{ route('admin.purchases.index', ['q' => $supplier->company_name ?: $supplier->name]) }}" class="block text-primary-600 font-medium hover:underline underline-offset-4">Purchases from this supplier</a>
            </section>
        </aside>
    </div>
</x-layouts.admin>
