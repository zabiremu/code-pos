<x-layouts.admin title="Brands">
    <x-list-toolbar intro="Manufacturers and labels your products come under." :create-route="route('admin.brands.create')" create-label="Add brand"
                    :search="$search" placeholder="Search brands" :action="route('admin.brands.index')" />

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Brand</th>
                    <th class="px-5 py-3 font-medium text-right">Products</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($brands as $brand)
                    <tr class="group hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.brands.edit', $brand) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $brand->name }}</a>
                            @if ($brand->description)
                                <div class="text-xs text-zinc-500 mt-0.5 max-w-xl truncate">{{ $brand->description }}</div>
                            @endif
                            <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="text-zinc-500 hover:text-primary-600">Edit</a>
                                <a href="{{ route('admin.products.index', ['brand_id' => $brand->id]) }}" class="text-zinc-500 hover:text-primary-600">View products</a>
                                <x-delete-button :action="route('admin.brands.destroy', $brand)" :confirm="'Delete the brand '.$brand->name.'?'" />
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-zinc-600">{{ $brand->products_count }}</td>
                        <td class="px-5 py-3 text-right"><span class="{{ $brand->is_active ? 'badge-green' : 'badge-gray' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @empty
                    <x-empty-row colspan="3" noun="brands" :search="$search" :clear-href="route('admin.brands.index')" :create-href="route('admin.brands.create')" create-label="Add your first brand" />
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $brands->links() }}</div>
</x-layouts.admin>
