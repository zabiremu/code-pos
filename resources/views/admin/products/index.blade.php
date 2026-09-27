@php
    $money = fn ($v) => number_format((float) $v, 2);
    $query = fn (array $extra) => array_filter(array_merge(['q' => $search ?: null], $filters, $extra));
@endphp
<x-layouts.admin title="Products">
    <x-list-toolbar intro="Everything you sell, with prices and stock across warehouses." :create-route="route('admin.products.create')" create-label="Add product"
                    :search="$search" placeholder="Name or SKU" :action="route('admin.products.index')">
        <x-tab-link :href="route('admin.products.index', $query(['stock' => null]))" :active="empty($filters['stock'])">All</x-tab-link>
        <x-tab-link :href="route('admin.products.index', $query(['stock' => 'low']))" :active="($filters['stock'] ?? null) === 'low'">Low stock</x-tab-link>
        <x-tab-link :href="route('admin.products.index', $query(['stock' => 'out']))" :active="($filters['stock'] ?? null) === 'out'">Out of stock</x-tab-link>
        <x-slot:hidden>
            @foreach ($filters as $key => $value)
                @if ($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
            @endforeach
        </x-slot:hidden>
    </x-list-toolbar>

    <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap items-center gap-2 mb-4 text-sm">
        @if ($search)<input type="hidden" name="q" value="{{ $search }}">@endif
        @if (! empty($filters['stock']))<input type="hidden" name="stock" value="{{ $filters['stock'] }}">@endif
        <select name="category_id" class="input w-auto" aria-label="Category" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="brand_id" class="input w-auto" aria-label="Brand" onchange="this.form.submit()">
            <option value="">All brands</option>
            @foreach ($brands as $b)
                <option value="{{ $b->id }}" @selected(($filters['brand_id'] ?? '') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
        <noscript><button class="btn-secondary">Filter</button></noscript>
        @if (! empty($filters['category_id']) || ! empty($filters['brand_id']))
            <a href="{{ route('admin.products.index', array_filter(['q' => $search ?: null, 'stock' => $filters['stock'] ?? null])) }}" class="text-primary-600 hover:underline underline-offset-4">Clear filters</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Product</th>
                    <th class="px-5 py-3 font-medium">Category / brand</th>
                    <th class="px-5 py-3 font-medium text-right">Cost</th>
                    <th class="px-5 py-3 font-medium text-right">Sale price</th>
                    <th class="px-5 py-3 font-medium text-right">Stock</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($products as $product)
                    <tr class="group hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3 align-top">
                          <div class="flex items-start gap-3">
                            <a href="{{ route('admin.products.edit', $product) }}" class="w-11 h-11 rounded-lg ring-1 ring-zinc-200 bg-zinc-50 overflow-hidden shrink-0 flex items-center justify-center" tabindex="-1" aria-hidden="true">
                                @if ($product->image_path)
                                    <img src="{{ $product->imageUrl() }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                @else
                                    <span class="text-sm font-semibold text-zinc-300">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                                @endif
                            </a>
                            <div class="min-w-0">
                            <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $product->name }}</a>
                            @if ($product->sku)<div class="text-xs text-zinc-500 mt-0.5">SKU {{ $product->sku }}</div>@endif
                            <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-zinc-500 hover:text-primary-600">Edit</a>
                                <x-delete-button :action="route('admin.products.destroy', $product)" :confirm="'Delete '.$product->name.'? This can\'t be undone.'" />
                            </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-5 py-3 align-top text-zinc-600">
                            {{ $product->category?->name ?? 'Uncategorised' }}
                            @if ($product->brand)<div class="text-xs text-zinc-500 mt-0.5">{{ $product->brand->name }}</div>@endif
                        </td>
                        <td class="px-5 py-3 align-top text-right tabular-nums text-zinc-600">{{ $money($product->purchase_price) }}</td>
                        <td class="px-5 py-3 align-top text-right tabular-nums">
                            <span class="font-medium">{{ $money($product->base_price) }}</span>
                            @if ($product->regular_price && $product->regular_price > $product->base_price)
                                <div class="text-xs text-zinc-400 line-through">{{ $money($product->regular_price) }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3 align-top text-right whitespace-nowrap">
                            @if ($product->track_stock)
                                <span class="{{ $product->stock_quantity <= 0 ? 'text-primary-600 font-semibold' : ($product->isLowStock() ? 'text-primary-600' : 'text-zinc-700') }}">
                                    <x-qty :value="$product->stock_quantity" :unit="$product->unit?->short_name" />
                                </span>
                            @else
                                <span class="text-zinc-400">Not tracked</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 align-top text-right">
                            <span class="{{ $product->is_available ? 'badge-green' : 'badge-gray' }}">{{ $product->is_available ? 'On sale' : 'Hidden' }}</span>
                        </td>
                    </tr>
                @empty
                    <x-empty-row colspan="6" noun="products" :search="$search" :clear-href="route('admin.products.index')" :create-href="route('admin.products.create')" create-label="Add your first product" />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-3 text-xs text-zinc-500">
        <span>{{ $products->total() }} {{ $products->total() === 1 ? 'product' : 'products' }}</span>
        {{ $products->links() }}
    </div>
</x-layouts.admin>
