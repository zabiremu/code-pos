<x-layouts.admin :title="'New Order'">
    <form method="POST" action="{{ route('pos.orders.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4 max-w-md">
        @csrf
        <div>
            <label class="block text-sm mb-1">Type</label>
            <select name="type" class="w-full rounded border-gray-300 text-sm" x-data x-on:change="$el.form.table_id.disabled = ($event.target.value !== 'dine_in')">
                <option value="dine_in">Dine-in</option>
                <option value="takeaway">Takeaway</option>
                <option value="delivery">Delivery</option>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">Table (dine-in only)</label>
            <select name="table_id" class="w-full rounded border-gray-300 text-sm">
                <option value="">—</option>
                @foreach ($tables as $table)
                    <option value="{{ $table->id }}">{{ $table->floor->name }} / {{ $table->label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">Guests</label>
            <input type="number" name="guest_count" value="1" min="1" class="w-full rounded border-gray-300 text-sm">
        </div>
        <button class="bg-gray-900 text-white text-sm rounded px-4 py-2">Open order</button>
    </form>
</x-layouts.admin>
