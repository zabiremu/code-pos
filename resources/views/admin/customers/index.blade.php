@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Customers">
    <x-list-toolbar intro="People who buy from you, and what they owe." :create-route="route('admin.customers.create')" create-label="Add customer"
                    :search="$search" placeholder="Name or phone" :action="route('admin.customers.index')">
        <x-tab-link :href="route('admin.customers.index', array_filter(['q' => $search ?: null]))" :active="$filter === 'all'">All</x-tab-link>
        <x-tab-link :href="route('admin.customers.index', array_filter(['filter' => 'due', 'q' => $search ?: null]))" :active="$filter === 'due'">Owe money</x-tab-link>
        <x-slot:hidden>@if ($filter === 'due')<input type="hidden" name="filter" value="due">@endif</x-slot:hidden>
    </x-list-toolbar>

    @if ($totalDue > 0)
        <p class="text-sm text-zinc-600 mb-3">Customers owe you <span class="font-semibold text-primary-700 tabular-nums">{{ $money($totalDue) }}</span> in total.</p>
    @endif

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Customer</th>
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium text-right">Sales</th>
                    <th class="px-5 py-3 font-medium text-right">Owes</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($customers as $c)
                    <tr class="group hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.customers.show', $c) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $c->name }}</a>
                            <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                <a href="{{ route('admin.customers.show', $c) }}" class="text-zinc-500 hover:text-primary-600">Account</a>
                                @if ($c->due_amount > 0)<a href="{{ route('admin.customer-receipts.create', ['customer_id' => $c->id]) }}" class="text-zinc-500 hover:text-primary-600">Collect</a>@endif
                                <a href="{{ route('admin.customers.edit', $c) }}" class="text-zinc-500 hover:text-primary-600">Edit</a>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-zinc-600 whitespace-nowrap">
                            @if ($c->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->phone) }}" class="hover:text-primary-600">{{ $c->phone }}</a>@else<span class="text-zinc-300">—</span>@endif
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $c->sales_count }}</td>
                        <td class="px-5 py-3 text-right tabular-nums whitespace-nowrap {{ $c->due_amount > 0 ? 'text-primary-700 font-semibold' : 'text-zinc-400' }}">{{ $c->due_amount > 0 ? $money($c->due_amount) : '—' }}</td>
                        <td class="px-5 py-3 text-right"><span class="{{ $c->is_active ? 'badge-green' : 'badge-gray' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" :noun="$filter === 'due' ? 'customers who owe money' : 'customers'" :search="$search" :clear-href="route('admin.customers.index')" :create-href="route('admin.customers.create')" create-label="Add your first customer" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $customers->links() }}</div>
</x-layouts.admin>
