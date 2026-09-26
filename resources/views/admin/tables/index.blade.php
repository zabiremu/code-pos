<x-layouts.admin :title="'Tables'">
    <form method="POST" action="{{ route('admin.tables.store') }}" class="card p-5 mb-6 flex flex-wrap gap-3 items-end">
        @csrf
        <div>
            <label class="field-label">Floor</label>
            <select name="floor_id" required class="input">
                @foreach ($floors as $floor)
                    <option value="{{ $floor->id }}">{{ $floor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Label</label>
            <input type="text" name="label" required class="input w-24" placeholder="T1">
        </div>
        <div>
            <label class="field-label">Seats</label>
            <input type="number" name="seats" value="2" min="1" class="input w-20">
        </div>
        <button class="btn-primary">Add table</button>
    </form>

    <div class="card divide-y">
        @foreach ($tables as $table)
            <div class="flex items-center justify-between px-5 py-3">
                <span>{{ $table->label }} &middot; {{ $table->floor->name }} &middot; {{ $table->seats }} seats</span>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.tables.qr', $table) }}" class="btn-ghost">QR code</a>
                    <form method="POST" action="{{ route('admin.tables.destroy', $table) }}" onsubmit="return confirm('Delete this table?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
