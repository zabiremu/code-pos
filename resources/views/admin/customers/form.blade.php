<x-layouts.admin :title="$customer->exists ? $customer->name : 'Add customer'">
    <x-back-link :href="$customer->exists ? route('admin.customers.show', $customer) : route('admin.customers.index')">{{ $customer->exists ? 'Customer account' : 'All customers' }}</x-back-link>

    <form method="POST" action="{{ $customer->exists ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" class="card max-w-2xl">
        @csrf
        @if ($customer->exists) @method('PUT') @endif
        <div class="px-5">
            <x-form-row label="Name" for="name" :required="true">
                <input id="name" name="name" value="{{ old('name', $customer->name) }}" required maxlength="150" class="input">
            </x-form-row>
            <x-form-row label="Phone" for="phone" hint="Used to find them at the register. Must be unique.">
                <input id="phone" type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="30" class="input" style="max-width:16rem">
            </x-form-row>
            <x-form-row label="Email" for="email">
                <input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="input">
            </x-form-row>
            <x-form-row label="Address" for="address">
                <textarea id="address" name="address" rows="2" class="input">{{ old('address', $customer->address) }}</textarea>
            </x-form-row>
            <x-form-row label="Credit limit" for="credit_limit" hint="The most they can owe at once. Leave blank for no limit.">
                <input id="credit_limit" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" class="input" style="max-width:12rem">
            </x-form-row>
            <x-form-row label="Notes" for="notes">
                <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $customer->notes) }}</textarea>
            </x-form-row>
            <x-checkbox-row label="Status" name="is_active" :checked="$customer->is_active" text="Active" hint="Inactive customers don't appear at the register." />
        </div>
        <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
            <button class="btn-primary">{{ $customer->exists ? 'Save changes' : 'Add customer' }}</button>
            <a href="{{ $customer->exists ? route('admin.customers.show', $customer) : route('admin.customers.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>

    @if ($customer->exists)
        <div class="card mt-6 p-5 flex flex-wrap items-center justify-between gap-4 max-w-2xl">
            <div>
                <h2 class="text-sm font-semibold">Delete this customer</h2>
                <p class="text-sm text-zinc-500 mt-0.5">Only possible if they have no sales. Otherwise mark them inactive.</p>
            </div>
            <x-delete-button variant="danger" label="Delete customer" :action="route('admin.customers.destroy', $customer)" :confirm="'Delete '.$customer->name.'?'" />
        </div>
    @endif
</x-layouts.admin>
