<x-layouts.admin :title="$brand->exists ? $brand->name : 'Add brand'">
    <x-back-link :href="route('admin.brands.index')">All brands</x-back-link>

    <form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" class="card max-w-2xl">
        @csrf
        @if ($brand->exists) @method('PUT') @endif
        <div class="px-5">
            <x-form-row label="Name" for="name" :required="true">
                <input id="name" name="name" value="{{ old('name', $brand->name) }}" required maxlength="150" class="input">
            </x-form-row>
            <x-form-row label="Description" for="description">
                <textarea id="description" name="description" rows="3" class="input">{{ old('description', $brand->description) }}</textarea>
            </x-form-row>
            <x-checkbox-row label="Status" name="is_active" :checked="$brand->is_active" text="Active" hint="Inactive brands are hidden when adding products." />
        </div>
        <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
            <button class="btn-primary">{{ $brand->exists ? 'Save changes' : 'Add brand' }}</button>
            <a href="{{ route('admin.brands.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
