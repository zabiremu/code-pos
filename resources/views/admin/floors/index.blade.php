<x-layouts.admin :title="'Floors & Tables'">
    <div class="grid gap-4">
        @foreach ($floors as $floor)
            <div class="card p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-medium">{{ $floor->name }}</h2>
                    <form method="POST" action="{{ route('admin.floors.destroy', $floor) }}" onsubmit="return confirm('Delete this floor?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost">Delete floor</button>
                    </form>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($floor->tables as $table)
                        @php
                            $color = match($table->status) {
                                'free' => 'badge-green',
                                'occupied' => 'badge-dark',
                                'reserved' => 'badge-gray',
                                default => 'badge-gray',
                            };
                        @endphp
                        <span class="{{ $color }}">{{ $table->label }} ({{ $table->seats }})</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.floors.store') }}" class="card p-5 mt-6 flex gap-3 items-end max-w-md">
        @csrf
        <div class="flex-1">
            <label class="field-label">New floor name</label>
            <input type="text" name="name" required class="w-full input">
        </div>
        <button class="btn-primary">Add floor</button>
    </form>
</x-layouts.admin>
