<x-layouts.admin :title="'Menu Items'">
    <div class="flex justify-end mb-4">
        <a href="{{ route('admin.menu-items.create') }}" class="btn-primary">Add item</a>
    </div>

    <div class="card divide-y">
        @foreach ($items as $item)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium">{{ $item->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $item->category->name }} &middot; {{ number_format($item->base_price, 2) }}</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="{{ $item->is_available ? 'badge-green' : 'badge-gray' }}">
                        {{ $item->is_available ? 'Available' : 'Hidden' }}
                    </span>
                    <form method="POST" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('Delete this item?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{ $items->links() }}
</x-layouts.admin>
