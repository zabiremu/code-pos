<x-layouts.admin :title="'Floors & Tables'">
    <div class="grid gap-4">
        @foreach ($floors as $floor)
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-medium">{{ $floor->name }}</h2>
                    <form method="POST" action="{{ route('admin.floors.destroy', $floor) }}" onsubmit="return confirm('Delete this floor?')">
                        @csrf @method('DELETE')
                        <button class="text-sm text-red-600">Delete floor</button>
                    </form>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($floor->tables as $table)
                        @php
                            $color = match($table->status) {
                                'free' => 'bg-green-100 text-green-800',
                                'occupied' => 'bg-red-100 text-red-800',
                                'reserved' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="px-3 py-1 rounded text-xs {{ $color }}">{{ $table->label }} ({{ $table->seats }})</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.floors.store') }}" class="bg-white rounded-lg shadow p-5 mt-6 flex gap-3 items-end max-w-md">
        @csrf
        <div class="flex-1">
            <label class="block text-xs text-gray-500 mb-1">New floor name</label>
            <input type="text" name="name" required class="w-full rounded border-gray-300 text-sm">
        </div>
        <button class="bg-gray-900 text-white text-sm rounded px-4 py-2">Add floor</button>
    </form>
</x-layouts.admin>
