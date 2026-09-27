<x-layouts.admin :title="$warehouse->exists ? $warehouse->name : 'Add warehouse'">
    <x-back-link :href="route('admin.warehouses.index')">All warehouses</x-back-link>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <form method="POST" action="{{ $warehouse->exists ? route('admin.warehouses.update', $warehouse) : route('admin.warehouses.store') }}" class="card">
            @csrf
            @if ($warehouse->exists) @method('PUT') @endif
            <div class="px-5">
                <x-form-row label="Name" for="name" :required="true">
                    <input id="name" name="name" value="{{ old('name', $warehouse->name) }}" required maxlength="150" class="input">
                </x-form-row>
                <x-form-row label="Code" for="code" hint="Short label, e.g. MAIN, GDN-2">
                    <input id="code" name="code" value="{{ old('code', $warehouse->code) }}" maxlength="30" class="input" style="max-width:12rem">
                </x-form-row>
                <x-form-row label="Phone" for="phone">
                    <input id="phone" type="tel" name="phone" value="{{ old('phone', $warehouse->phone) }}" maxlength="50" class="input">
                </x-form-row>
                <x-form-row label="Address" for="address">
                    <textarea id="address" name="address" rows="3" class="input">{{ old('address', $warehouse->address) }}</textarea>
                </x-form-row>
                <x-checkbox-row label="Default" name="is_default" :checked="$warehouse->is_default" text="Sales take stock from this warehouse" hint="Only one warehouse can be the default." />
                <x-checkbox-row label="Status" name="is_active" :checked="$warehouse->is_active" text="Active" />
            </div>
            <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
                <button class="btn-primary">{{ $warehouse->exists ? 'Save changes' : 'Add warehouse' }}</button>
                <a href="{{ route('admin.warehouses.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>

        @if ($warehouse->exists)
            <section class="card overflow-hidden" aria-labelledby="stock-heading">
                <div class="px-5 pt-5 pb-3">
                    <h2 id="stock-heading" class="font-display text-xl">Stock here</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-2.5 font-medium">Product</th>
                            <th class="px-5 py-2.5 font-medium text-right">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($warehouse->stocks as $stock)
                            <tr>
                                <td class="px-5 py-2.5"><a href="{{ route('admin.products.edit', $stock->product_id) }}" class="hover:text-primary-600">{{ $stock->product?->name }}</a></td>
                                <td class="px-5 py-2.5 text-right {{ $stock->quantity < 0 ? 'text-primary-600' : '' }}"><x-qty :value="$stock->quantity" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-5 py-10 text-center text-zinc-500">Nothing in stock here yet. Stock arrives through goods received (GRN).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @endif
    </div>

    @if ($warehouse->exists && ! $warehouse->is_default)
        <div class="card mt-6 p-5 flex flex-wrap items-center justify-between gap-4" style="max-width:48rem">
            <div>
                <h2 class="text-sm font-semibold">Delete this warehouse</h2>
                <p class="text-sm text-zinc-500 mt-0.5">Only possible when it holds no stock and isn't on any purchase or GRN.</p>
            </div>
            <x-delete-button variant="danger" label="Delete warehouse" :action="route('admin.warehouses.destroy', $warehouse)" :confirm="'Delete the warehouse '.$warehouse->name.'?'" />
        </div>
    @endif
</x-layouts.admin>
