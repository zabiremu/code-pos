<x-layouts.admin :title="'Orders'">
    <div class="flex justify-end mb-4">
        <a href="{{ route('pos.orders.create') }}" class="btn-primary">New order</a>
    </div>

    <div class="card divide-y">
        @foreach ($orders as $order)
            @php
                $statusBadge = match ($order->status) {
                    'open' => 'badge-gray',
                    'sent' => 'badge-blue',
                    'served' => 'badge-green',
                    default => 'badge-gray',
                };
            @endphp
            <a href="{{ route('pos.orders.show', $order) }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                <div>
                    <p class="font-medium">
                        #{{ $order->id }} &middot; {{ $order->table?->label ?? ucfirst($order->type) }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $order->items->count() }} item(s) &middot; {{ $order->waiter?->name }}</p>
                </div>
                <span class="{{ $statusBadge }}">{{ ucfirst($order->status) }}</span>
            </a>
        @endforeach

        @if ($orders->isEmpty())
            <p class="px-5 py-12 text-sm text-gray-400 text-center">No open orders right now.</p>
        @endif
    </div>

    {{ $orders->links() }}
</x-layouts.admin>
