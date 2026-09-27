<x-layouts.admin :title="$unit->exists ? 'Edit unit' : 'Add unit'">
    <x-back-link :href="route('admin.units.index')">All units</x-back-link>

    <form method="POST" action="{{ $unit->exists ? route('admin.units.update', $unit) : route('admin.units.store') }}" class="card max-w-2xl">
        @csrf
        @if ($unit->exists) @method('PUT') @endif
        <div class="px-5">
            <x-form-row label="Name" for="name" :required="true" hint="e.g. Kilogram">
                <input id="name" name="name" value="{{ old('name', $unit->name) }}" required maxlength="100" class="input">
            </x-form-row>
            <x-form-row label="Short name" for="short_name" :required="true" hint="Shown next to quantities, e.g. kg">
                <input id="short_name" name="short_name" value="{{ old('short_name', $unit->short_name) }}" required maxlength="20" class="input" style="max-width:10rem">
            </x-form-row>
            <x-checkbox-row label="Fractions" name="allow_decimal" :checked="$unit->allow_decimal" text="Allow quantities like 1.5" hint="On for weight and volume, off for pieces." />
            <x-checkbox-row label="Status" name="is_active" :checked="$unit->is_active" text="Active" />
        </div>
        <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
            <button class="btn-primary">{{ $unit->exists ? 'Save changes' : 'Add unit' }}</button>
            <a href="{{ route('admin.units.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
