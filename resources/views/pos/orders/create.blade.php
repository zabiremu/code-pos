<x-layouts.admin :title="'New Order'">
    <form method="POST" action="{{ route('pos.orders.store') }}" class="card p-6 space-y-4 max-w-md">
        @csrf
        <div>
            <label class="field-label">Type</label>
            <select name="type" class="w-full input" x-data x-on:change="$el.form.table_id.disabled = ($event.target.value !== 'dine_in')">
                <option value="dine_in">Dine-in</option>
                <option value="takeaway">Takeaway</option>
                <option value="delivery">Delivery</option>
            </select>
        </div>
        <div>
            <label class="field-label">Table (dine-in only)</label>
            <select name="table_id" class="w-full input">
                <option value="">—</option>
                @foreach ($tables as $table)
                    <option value="{{ $table->id }}">{{ $table->floor->name }} / {{ $table->label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Guests</label>
            <input type="number" name="guest_count" value="1" min="1" class="w-full input">
        </div>
        <button class="btn-primary">Open order</button>
    </form>
</x-layouts.admin>
