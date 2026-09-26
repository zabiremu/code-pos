<x-layouts.admin :title="'Order #'.$order->id">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card divide-y">
            @foreach ($order->items as $item)
                @php
                    $itemBadge = match ($item->status) {
                        'pending' => 'badge-gray',
                        'sent', 'preparing' => 'badge-blue',
                        'ready' => 'badge-yellow',
                        'served' => 'badge-green',
                        'cancelled' => 'badge-red',
                        default => 'badge-gray',
                    };
                @endphp
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="font-medium">{{ $item->quantity }}× {{ $item->menuItem->name }}</p>
                        @if ($item->modifiers->count())
                            <p class="text-xs text-gray-500">{{ $item->modifiers->pluck('name')->join(', ') }}</p>
                        @endif
                        @if ($item->notes)
                            <p class="text-xs text-gray-400 italic">{{ $item->notes }}</p>
                        @endif
                    </div>
                    <span class="{{ $itemBadge }}">{{ ucfirst($item->status) }}</span>
                </div>
            @endforeach

            @if ($order->items->isEmpty())
                <p class="px-5 py-6 text-sm text-gray-400">No items yet — add some from the menu on the right.</p>
            @endif
        </div>

        <div class="space-y-4">
            <form method="POST" action="{{ route('pos.orders.items.store', $order) }}" class="card p-4 space-y-2">
                @csrf
                <label class="block text-sm">Add item</label>
                <select name="menu_item_id" required class="w-full input">
                    @foreach ($menuItems as $mi)
                        <option value="{{ $mi->id }}">{{ $mi->name }} ({{ number_format($mi->base_price, 2) }})</option>
                    @endforeach
                </select>
                <input type="number" name="quantity" value="1" min="1" class="w-full input">
                <input type="text" name="notes" placeholder="Notes (e.g. no onion)" class="w-full input">
                <button class="w-full btn-secondary">Add to order</button>
            </form>

            <form method="POST" action="{{ route('pos.orders.send-to-kitchen', $order) }}">
                @csrf
                <button class="w-full btn-primary">Send to kitchen</button>
            </form>

            <form method="POST" action="{{ route('pos.bills.store', $order) }}">
                @csrf
                <button class="w-full btn-success">Generate bill</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
