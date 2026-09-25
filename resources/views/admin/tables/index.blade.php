<x-layouts.admin :title="'Tables'">
    <form method="POST" action="{{ route('admin.tables.store') }}" class="bg-white rounded-lg shadow p-5 mb-6 flex flex-wrap gap-3 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1">Floor</label>
            <select name="floor_id" required class="rounded border-gray-300 text-sm">
                @foreach ($floors as $floor)
                    <option value="{{ $floor->id }}">{{ $floor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Label</label>
            <input type="text" name="label" required class="rounded border-gray-300 text-sm w-24" placeholder="T1">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Seats</label>
            <input type="number" name="seats" value="2" min="1" class="rounded border-gray-300 text-sm w-20">
        </div>
        <button class="bg-gray-900 text-white text-sm rounded px-4 py-2">Add table</button>
    </form>

    <div class="bg-white rounded-lg shadow divide-y">
        @foreach ($tables as $table)
            <div class="flex items-center justify-between px-5 py-3">
                <span>{{ $table->label }} &middot; {{ $table->floor->name }} &middot; {{ $table->seats }} seats</span>
                <form method="POST" action="{{ route('admin.tables.destroy', $table) }}" onsubmit="return confirm('Delete this table?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600">Delete</button>
                </form>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
