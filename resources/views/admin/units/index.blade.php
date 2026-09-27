<x-layouts.admin title="Units">
    <x-list-toolbar intro="How products are counted: piece, kilogram, box, litre." :create-route="route('admin.units.create')" create-label="Add unit"
                    :search="$search" placeholder="Search units" :action="route('admin.units.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Unit</th>
                    <th class="px-5 py-3 font-medium">Short name</th>
                    <th class="px-5 py-3 font-medium">Fractions</th>
                    <th class="px-5 py-3 font-medium text-right">Products</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($units as $unit)
                    <tr class="group hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.units.edit', $unit) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $unit->name }}</a>
                            <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                <a href="{{ route('admin.units.edit', $unit) }}" class="text-zinc-500 hover:text-primary-600">Edit</a>
                                <x-delete-button :action="route('admin.units.destroy', $unit)" :confirm="'Delete the unit '.$unit->name.'?'" />
                            </div>
                        </td>
                        <td class="px-5 py-3 text-zinc-600">{{ $unit->short_name }}</td>
                        <td class="px-5 py-3 text-zinc-600">{{ $unit->allow_decimal ? 'Allowed (e.g. 1.5)' : 'Whole numbers' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $unit->products_count }}</td>
                        <td class="px-5 py-3 text-right"><span class="{{ $unit->is_active ? 'badge-green' : 'badge-gray' }}">{{ $unit->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <x-empty-row colspan="5" noun="units" :search="$search" :clear-href="route('admin.units.index')" :create-href="route('admin.units.create')" create-label="Add your first unit" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $units->links() }}</div>
</x-layouts.admin>
