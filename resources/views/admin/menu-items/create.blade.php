<x-layouts.admin :title="'Add Menu Item'">
    <form method="POST" action="{{ route('admin.menu-items.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" required class="w-full rounded border-gray-300 text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">Category</label>
                <select name="category_id" required class="w-full rounded border-gray-300 text-sm">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Base price</label>
                <input type="number" step="0.01" name="base_price" required class="w-full rounded border-gray-300 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full rounded border-gray-300 text-sm"></textarea>
        </div>

        <div>
            <label class="block text-sm mb-1">Modifier groups</label>
            <div class="flex flex-wrap gap-3">
                @foreach ($modifierGroups as $group)
                    <label class="text-sm flex items-center gap-1">
                        <input type="checkbox" name="modifier_groups[]" value="{{ $group->id }}">
                        {{ $group->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <label class="text-sm flex items-center gap-2">
            <input type="checkbox" name="is_available" value="1" checked>
            Available for sale
        </label>

        <button class="bg-gray-900 text-white text-sm rounded px-4 py-2">Save item</button>
    </form>
</x-layouts.admin>
