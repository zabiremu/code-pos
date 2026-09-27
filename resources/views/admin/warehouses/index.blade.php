<x-layouts.admin title="Warehouses">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-sm text-zinc-500">Where stock is kept. Sales take stock from the default warehouse.</p>
        <a href="{{ route('admin.warehouses.create') }}" class="btn-primary shrink-0">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            Add warehouse
        </a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Warehouse</th>
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium text-right">Products in stock</th>
                    <th class="px-5 py-3 font-medium text-right">Total units</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($warehouses as $warehouse)
                    <tr class="group hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $warehouse->label() }}</a>
                            @if ($warehouse->address)
                                <div class="text-xs text-zinc-500 mt-0.5 max-w-md truncate">{{ $warehouse->address }}</div>
                            @endif
                            <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="text-zinc-500 hover:text-primary-600">Edit &amp; view stock</a>
                                @unless ($warehouse->is_default)
                                    <x-delete-button :action="route('admin.warehouses.destroy', $warehouse)" :confirm="'Delete the warehouse '.$warehouse->name.'?'" />
                                @endunless
                            </div>
                        </td>
                        <td class="px-5 py-3 text-zinc-600">{{ $warehouse->phone ?: '—' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $warehouse->products_in_stock }}</td>
                        <td class="px-5 py-3 text-right text-zinc-600"><x-qty :value="$warehouse->total_units ?? 0" /></td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            @if ($warehouse->is_default)<span class="badge-primary">Default</span>@endif
                            <span class="{{ $warehouse->is_active ? 'badge-green' : 'badge-gray' }}">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
