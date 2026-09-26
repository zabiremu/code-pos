<x-layouts.admin :title="'Products'">
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.products.create') }}" class="btn-primary">Add product</a>
    </div>

    <div class="card divide-y">
        @foreach ($products as $product)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium">{{ $product->name }}</p>
                    <p class="text-xs text-zinc-500">
                        {{ $product->category?->name ?? 'Uncategorized' }} &middot; {{ number_format($product->base_price, 2) }}
                        @if ($product->sku)
                            &middot; SKU {{ $product->sku }}
                        @endif
                        @if ($product->track_stock)
                            &middot; {{ $product->stock_quantity }} in stock
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="{{ $product->is_available ? 'badge-green' : 'badge-gray' }}">
                        {{ $product->is_available ? 'Available' : 'Hidden' }}
                    </span>
                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{ $products->links() }}
</x-layouts.admin>
