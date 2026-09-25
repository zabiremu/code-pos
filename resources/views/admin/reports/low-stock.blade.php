<x-layouts.admin :title="'Low Stock'">
    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($ingredients as $ingredient)
            <div class="flex justify-between px-5 py-3 text-sm">
                <span>{{ $ingredient->name }}</span>
                <span class="text-red-600">{{ $ingredient->stock_qty }} {{ $ingredient->unit }} left (threshold {{ $ingredient->low_stock_threshold }})</span>
            </div>
        @empty
            <p class="px-5 py-6 text-sm text-gray-400">Nothing low on stock.</p>
        @endforelse
    </div>
</x-layouts.admin>
