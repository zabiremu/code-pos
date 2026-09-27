@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin :title="$customer->name">
    <x-back-link :href="route('admin.customers.index')">All customers</x-back-link>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2 space-y-6">
            <section class="rounded-2xl sidebar-surface text-bone p-6 md:p-8" aria-labelledby="due-heading">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 id="due-heading" class="text-sm text-bone/70">Owes you</h2>
                        <p class="font-display text-5xl leading-none mt-3 tabular-nums">{{ $money($due) }}</p>
                        @if ($customer->credit_limit !== null)
                            <p class="text-sm text-bone/60 mt-2">Credit limit {{ $money($customer->credit_limit) }}</p>
                        @endif
                    </div>
                    @if ($due > 0)
                        <a href="{{ route('admin.customer-receipts.create', ['customer_id' => $customer->id]) }}"
                           class="btn bg-bone text-primary-700 hover:bg-white shadow-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brass">Collect payment</a>
                    @endif
                </div>
                <dl class="grid grid-cols-3 gap-4 mt-8 text-sm">
                    <div><dt class="text-bone/60">Sales</dt><dd class="tabular-nums mt-1 text-base">{{ $stats['sales'] }}</dd></div>
                    <div><dt class="text-bone/60">Spent in total</dt><dd class="tabular-nums mt-1 text-base">{{ $money($stats['spent']) }}</dd></div>
                    <div><dt class="text-bone/60">Last visit</dt><dd class="mt-1 text-base">{{ $stats['last']?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="card overflow-hidden" aria-labelledby="acct-heading">
                <div class="px-5 pt-5 pb-3 flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="acct-heading" class="font-display text-xl">Account</h2>
                    <p class="text-xs text-zinc-500">Newest first. Balance is what they owe after each entry.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                                <th class="px-5 py-2.5 font-medium">Date</th>
                                <th class="px-5 py-2.5 font-medium">Entry</th>
                                <th class="px-5 py-2.5 font-medium text-right">Bought</th>
                                <th class="px-5 py-2.5 font-medium text-right">Paid / returned</th>
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
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $e['owed'] ? $money($e['owed']) : '' }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-emerald-700">{{ $e['paid'] ? $money($e['paid']) : '' }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums font-medium">{{ $money($e['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No sales yet. Pick this customer at the register to link a sale.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5 text-sm space-y-3" aria-label="Contact">
                <div class="flex items-start justify-between gap-3">
                    <p class="font-medium text-base">{{ $customer->name }}</p>
                    <span class="{{ $customer->is_active ? 'badge-green' : 'badge-gray' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                @if ($customer->phone)<p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->phone) }}" class="hover:text-primary-600">{{ $customer->phone }}</a></p>@endif
                @if ($customer->email)<p><a href="mailto:{{ $customer->email }}" class="hover:text-primary-600">{{ $customer->email }}</a></p>@endif
                @if ($customer->address)<p class="text-zinc-600" style="white-space:pre-line">{{ $customer->address }}</p>@endif
                @if ($customer->notes)<p class="text-zinc-600 border-t border-zinc-100 pt-3" style="white-space:pre-line">{{ $customer->notes }}</p>@endif
                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn-secondary w-full justify-center">Edit details</a>
            </section>
        </aside>
    </div>
</x-layouts.admin>
