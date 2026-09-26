<x-layouts.admin :title="'Low Stock'">
    <div class="card divide-y">
        @forelse ($ingredients as $ingredient)
            <div class="flex justify-between px-5 py-3 text-sm">
                <span>{{ $ingredient->name }}</span>
                <span class="badge-red">{{ $ingredient->stock_qty }} {{ $ingredient->unit }} left (threshold {{ $ingredient->low_stock_threshold }})</span>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-zinc-400">Nothing low on stock.</p>
        @endforelse
    </div>
</x-layouts.admin>
