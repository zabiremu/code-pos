<x-layouts.admin :title="'Sale #'.$sale->id">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card divide-y">
            @foreach ($sale->items as $item)
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="font-medium">{{ $item->quantity }}× {{ $item->product->name }}</p>
                        @if ($item->notes)
                            <p class="text-xs text-zinc-400 italic">{{ $item->notes }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-zinc-600">{{ number_format($item->lineTotal(), 2) }}</span>
                        @if ($sale->status === 'open')
                            <form method="POST" action="{{ route('pos.sale-items.destroy', $item) }}" onsubmit="return confirm('Remove this item?')">
                                @csrf @method('DELETE')
                                <button class="btn-ghost text-xs">Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($sale->items->isEmpty())
                <p class="px-5 py-6 text-sm text-zinc-400">No items yet — add some from the product list on the right.</p>
            @endif
        </div>

        <div class="space-y-4">
            @if ($sale->status === 'open')
                <form method="POST" action="{{ route('pos.sales.items.store', $sale) }}" class="card p-4 space-y-2">
                    @csrf
                    <label class="block text-sm">Add product</label>
                    <select name="product_id" required class="w-full input">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ number_format($product->base_price, 2) }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="quantity" value="1" min="1" class="w-full input">
                    <input type="text" name="notes" placeholder="Notes (optional)" class="w-full input">
                    <button class="w-full btn-secondary">Add to sale</button>
                </form>

                <form method="POST" action="{{ route('pos.bills.store', $sale) }}">
                    @csrf
                    <button class="w-full btn-success">Generate bill</button>
                </form>
            @else
                <div class="card p-4 space-y-3">
                    @foreach ($sale->bills as $bill)
                        <a href="{{ route('pos.bills.show', $bill) }}" class="block btn-secondary text-center">
                            View bill #{{ $bill->id }} ({{ ucfirst($bill->status) }})
                        </a>
                    @endforeach
                </div>

                @if ($sale->status === 'billed')
                    <form method="POST" action="{{ route('pos.sales.close', $sale) }}">
                        @csrf
                        <button class="w-full btn-primary">Close sale</button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</x-layouts.admin>
