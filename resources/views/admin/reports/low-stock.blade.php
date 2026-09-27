<x-layouts.admin title="Low stock">
    <x-report-nav />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-sm text-zinc-500">Products at or below their low-stock alert level.</p>
        @if ($products->isNotEmpty())
            <a href="{{ route('admin.purchases.create') }}" class="btn-primary">Create purchase order</a>
        @endif
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Product</th>
                    <th class="px-5 py-3 font-medium text-right">In stock</th>
                    <th class="px-5 py-3 font-medium text-right">Alert at</th>
                    <th class="px-5 py-3 font-medium text-right">Last cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-5 py-3"><a href="{{ route('admin.products.edit', $product) }}" class="font-medium hover:text-primary-600">{{ $product->name }}</a>
                            @if ($product->sku)<div class="text-xs text-zinc-500">SKU {{ $product->sku }}</div>@endif</td>
                        <td class="px-5 py-3 text-right {{ $product->stock_quantity <= 0 ? 'text-primary-700 font-semibold' : 'text-primary-600' }}">
                            {{ $product->stock_quantity <= 0 ? 'Out' : '' }} @if ($product->stock_quantity > 0)<x-qty :value="$product->stock_quantity" :unit="$product->unit?->short_name" />@endif
                        </td>
                        <td class="px-5 py-3 text-right text-zinc-600"><x-qty :value="$product->low_stock_threshold" /></td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ number_format((float) $product->purchase_price, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-14 text-center text-zinc-500">Nothing is running low.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
