<x-layouts.admin :title="'Menu Items'">
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.menu-items.create') }}" class="bg-gray-900 text-white text-sm rounded px-4 py-2">Add item</a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @foreach ($items as $item)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium">{{ $item->name }}</p>
                    <p class="text-xs text-gray-500">{{ $item->category->name }} &middot; {{ number_format($item->base_price, 2) }}</p>
                </div>
                <div class="flex gap-3 text-sm">
                    <span class="{{ $item->is_available ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $item->is_available ? 'Available' : 'Hidden' }}
                    </span>
                    <form method="POST" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('Delete this item?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{ $items->links() }}
</x-layouts.admin>
