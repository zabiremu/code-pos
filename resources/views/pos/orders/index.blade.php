{{--
    Structured like a WordPress admin list-table screen (Posts/Pages):
    status tabs with counts, a search box above the table, a real <table>
    with a header row and a hover-revealed "row action" under the title
    cell instead of the whole row being one big link, zebra striping,
    and an item count next to pagination.
--}}
<x-layouts.admin :title="'Orders'">
    <div class="flex items-center justify-between gap-3 mb-4">
        <p class="text-sm text-zinc-500">Orders currently open, sent to the kitchen, or served.</p>
        <a href="{{ route('pos.orders.create') }}" class="btn-primary shrink-0">New order</a>
    </div>

    {{-- Status tabs --}}
    <div class="flex items-center gap-1 text-sm border-b border-zinc-200 mb-4">
        @php
            $tabs = [
                'all' => 'All',
                'open' => 'Open',
                'sent' => 'Sent',
                'served' => 'Served',
            ];
        @endphp
        @foreach ($tabs as $key => $label)
            <a href="{{ route('pos.orders.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}"
               class="px-3 py-2 -mb-px border-b-2 transition-colors {{ $status === $key ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-800' }}">
                {{ $label }} <span class="text-zinc-400">({{ $counts[$key] }})</span>
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="flex justify-end mb-3">
        <form method="GET" action="{{ route('pos.orders.index') }}" class="flex items-center gap-2">
            @if ($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="text" name="q" value="{{ $search }}" placeholder="Search order #, table, or type&hellip;" class="input w-56">
            <button class="btn-secondary">Search</button>
        </form>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 border-b border-zinc-100">
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3">Table / Type</th>
                    <th class="px-5 py-3">Items</th>
                    <th class="px-5 py-3">Waiter</th>
                    <th class="px-5 py-3 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($orders as $order)
                    @php
                        $statusBadge = match ($order->status) {
                            'open' => 'badge-gray',
                            'sent' => 'badge-dark',
                            'served' => 'badge-green',
                            default => 'badge-gray',
                        };
                    @endphp
                    <tr class="group even:bg-zinc-50/60 hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3 align-top">
                            <a href="{{ route('pos.orders.show', $order) }}" class="font-medium text-zinc-900 hover:text-primary-600">
                                #{{ $order->id }}
                            </a>
                            <div class="text-xs text-zinc-400 mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('pos.orders.show', $order) }}" class="hover:text-primary-600">View</a>
                            </div>
                        </td>
                        <td class="px-5 py-3 align-top text-zinc-600">
                            {{ $order->table?->label ?? ucfirst(str_replace('_', ' ', $order->type)) }}
                        </td>
                        <td class="px-5 py-3 align-top text-zinc-600">{{ $order->items->count() }}</td>
                        <td class="px-5 py-3 align-top text-zinc-600">{{ $order->waiter?->name ?? '—' }}</td>
                        <td class="px-5 py-3 align-top text-right">
                            <span class="{{ $statusBadge }}">{{ ucfirst($order->status) }}</span>
                        </td>
                    </tr>
                @endforeach

                @if ($orders->isEmpty())
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-sm text-zinc-400 text-center">
                            {{ $search !== '' ? 'No orders match your search.' : 'No orders in this view right now.' }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-3 text-xs text-zinc-500">
        <span>{{ $orders->total() }} {{ $orders->total() === 1 ? 'item' : 'items' }}</span>
        {{ $orders->links() }}
    </div>
</x-layouts.admin>
