<x-layouts.admin :title="'Orders'">
    <div class="flex justify-end mb-4">
        <a href="{{ route('pos.orders.create') }}" class="bg-gray-900 text-white text-sm rounded px-4 py-2">New order</a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @foreach ($orders as $order)
            <a href="{{ route('pos.orders.show', $order) }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50">
                <div>
                    <p class="font-medium">
                        #{{ $order->id }} &middot; {{ $order->table?->label ?? ucfirst($order->type) }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $order->items->count() }} item(s) &middot; {{ $order->waiter?->name }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded bg-gray-100">{{ ucfirst($order->status) }}</span>
            </a>
        @endforeach
    </div>

    {{ $orders->links() }}
</x-layouts.admin>
