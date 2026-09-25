<x-layouts.admin :title="'Categories'">
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Name</label>
                <input type="text" name="name" required class="rounded border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Parent</label>
                <select name="parent_id" class="rounded border-gray-300 text-sm">
                    <option value="">None</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="bg-gray-900 text-white text-sm rounded px-4 py-2">Add category</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @foreach ($categories as $category)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <span class="font-medium">{{ $category->name }}</span>
                    @if ($category->parent)
                        <span class="text-xs text-gray-400">under {{ $category->parent->name }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600">Delete</button>
                </form>
            </div>
        @endforeach
    </div>

    {{ $categories->links() }}
</x-layouts.admin>
