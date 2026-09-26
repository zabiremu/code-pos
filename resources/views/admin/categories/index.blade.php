<x-layouts.admin :title="'Categories'">
    <div class="card p-5 mb-6">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="field-label">Name</label>
                <input type="text" name="name" required class="input">
            </div>
            <div>
                <label class="field-label">Parent</label>
                <select name="parent_id" class="input">
                    <option value="">None</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary">Add category</button>
        </form>
    </div>

    <div class="card divide-y">
        @foreach ($categories as $category)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <span class="font-medium">{{ $category->name }}</span>
                    @if ($category->parent)
                        <span class="text-xs text-zinc-400">under {{ $category->parent->name }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf @method('DELETE')
                    <button class="btn-ghost">Delete</button>
                </form>
            </div>
        @endforeach
    </div>

    {{ $categories->links() }}
</x-layouts.admin>
