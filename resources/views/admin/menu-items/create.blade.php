<x-layouts.admin :title="'Add Menu Item'">
    <form method="POST" action="{{ route('admin.menu-items.store') }}" class="card p-6 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="field-label">Name</label>
            <input type="text" name="name" required class="w-full input">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="field-label">Category</label>
                <select name="category_id" required class="w-full input">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Base price</label>
                <input type="number" step="0.01" name="base_price" required class="w-full input">
            </div>
        </div>

        <div>
            <label class="field-label">Description</label>
            <textarea name="description" rows="3" class="w-full input"></textarea>
        </div>

        <div>
            <label class="field-label">Modifier groups</label>
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

        <button class="btn-primary">Save item</button>
    </form>
</x-layouts.admin>
