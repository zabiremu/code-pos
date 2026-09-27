@php
    $isEdit = $product->exists;
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<x-layouts.admin :title="$isEdit ? $product->name : 'Add product'">
    <x-back-link :href="route('admin.products.index')">All products</x-back-link>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <form method="POST" action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
              enctype="multipart/form-data"
              class="xl:col-span-2 space-y-6"
              x-data="{ cost: @js((float) old('purchase_price', $product->purchase_price ?? 0)), sale: @js((float) old('base_price', $product->base_price ?? 0)), track: @js((bool) old('track_stock', $product->track_stock)) }">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <section class="card" aria-labelledby="details-heading">
                <div class="card-body !pb-0"><h2 id="details-heading" class="text-base font-semibold">Details</h2></div>
                <div class="px-5">
                    <x-form-row label="Name" for="name" :required="true">
                        <input id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="150" class="input">
                    </x-form-row>
                    <x-form-row label="Photo" for="image" hint="JPG, PNG or WebP, up to 4 MB. Shown at the register.">
                        <div class="flex items-start gap-4" x-data="{ preview: @js($product->imageUrl()), removed: false }">
                            <div class="w-24 h-24 rounded-xl ring-1 ring-zinc-200 bg-zinc-50 overflow-hidden flex items-center justify-center shrink-0">
                                <img x-show="preview && !removed" :src="preview" alt="" class="w-full h-full object-cover">
                                <svg x-show="!preview || removed" class="w-8 h-8 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9" stroke-linejoin="round"/></svg>
                            </div>
                            <div class="space-y-2 min-w-0">
                                <label class="btn-secondary cursor-pointer">
                                    <span x-text="preview && !removed ? 'Change photo' : 'Choose photo'"></span>
                                    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only"
                                           @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); removed = false; $refs.remove && ($refs.remove.checked = false) }">
                                </label>
                                @if ($product->image_path)
                                    <label class="flex items-center gap-2 text-xs text-zinc-500">
                                        <input x-ref="remove" type="checkbox" name="remove_image" value="1" x-model="removed" class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500">
                                        Remove photo
                                    </label>
                                @endif
                            </div>
                        </div>
                    </x-form-row>
                    <x-form-row label="SKU / barcode" for="sku" hint="Must be unique.">
                        <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" maxlength="100" class="input" style="max-width:16rem">
                    </x-form-row>
                    <x-form-row label="Category" for="category_id">
                        <select id="category_id" name="category_id" class="input">
                            <option value="">Uncategorised</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-form-row label="Brand" for="brand_id" hint="Manage brands under Catalog.">
                        <select id="brand_id" name="brand_id" class="input">
                            <option value="">No brand</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-form-row label="Unit" for="unit_id">
                        <select id="unit_id" name="unit_id" class="input" style="max-width:16rem">
                            <option value="">No unit</option>
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}" @selected(old('unit_id', $product->unit_id) == $u->id)>{{ $u->name }} ({{ $u->short_name }})</option>
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-form-row label="Description" for="description">
                        <textarea id="description" name="description" rows="3" class="input">{{ old('description', $product->description) }}</textarea>
                    </x-form-row>
                </div>
            </section>

            <section class="card" aria-labelledby="pricing-heading">
                <div class="card-body !pb-0">
                    <h2 id="pricing-heading" class="text-base font-semibold">Pricing</h2>
                </div>
                <div class="px-5">
                    <x-form-row label="Purchase price" for="purchase_price" hint="What you pay the supplier. Updated automatically from the latest GRN.">
                        <input id="purchase_price" type="number" step="0.01" min="0" name="purchase_price" x-model.number="cost"
                               value="{{ old('purchase_price', $product->purchase_price) }}" class="input" style="max-width:12rem">
                    </x-form-row>
                    <x-form-row label="Sale price" for="base_price" :required="true" hint="What the POS charges.">
                        <input id="base_price" type="number" step="0.01" min="0" name="base_price" x-model.number="sale" required
                               value="{{ old('base_price', $product->base_price) }}" class="input" style="max-width:12rem">
                        <p class="text-xs mt-1.5" x-show="cost > 0 && sale > 0" x-cloak
                           :class="sale < cost ? 'text-primary-600' : 'text-zinc-500'"
                           x-text="sale < cost ? 'Selling below cost.' : 'Margin ' + ((sale - cost) / sale * 100).toFixed(1) + '% (' + (sale - cost).toFixed(2) + ' per unit)'"></p>
                    </x-form-row>
                    <x-form-row label="Regular price" for="regular_price" hint="Optional MRP / price before discount. Shown crossed out.">
                        <input id="regular_price" type="number" step="0.01" min="0" name="regular_price"
                               value="{{ old('regular_price', $product->regular_price) }}" class="input" style="max-width:12rem">
                    </x-form-row>
                    <x-form-row label="Tax rate %" for="tax_rate" hint="Leave blank to use the shop's default tax.">
                        <input id="tax_rate" type="number" step="0.01" min="0" max="100" name="tax_rate"
                               value="{{ old('tax_rate', $product->tax_rate) }}" class="input" style="max-width:8rem">
                    </x-form-row>
                </div>
            </section>

            <section class="card" aria-labelledby="stock-heading">
                <div class="card-body !pb-0"><h2 id="stock-heading" class="text-base font-semibold">Stock</h2></div>
                <div class="px-5">
                    <x-form-row label="Track stock" for="track_stock" hint="Off for services or items you never count.">
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                            <input type="hidden" name="track_stock" value="0">
                            <input id="track_stock" type="checkbox" name="track_stock" value="1" x-model="track"
                                   @checked(old('track_stock', $product->track_stock)) class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500">
                            Count stock for this product
                        </label>
                    </x-form-row>

                    @unless ($isEdit)
                        <x-form-row label="Opening stock" for="opening_stock" hint="What you already have on the shelf today." x-show="track">
                            <div class="flex flex-wrap gap-2">
                                <input id="opening_stock" type="number" step="0.001" min="0" name="opening_stock" value="{{ old('opening_stock') }}" placeholder="0" class="input" style="max-width:10rem">
                                <select name="opening_warehouse_id" class="input w-auto" aria-label="Opening stock warehouse">
                                    @foreach ($warehouses as $w)
                                        <option value="{{ $w->id }}" @selected(old('opening_warehouse_id') == $w->id)>{{ $w->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('opening_warehouse_id')<p class="text-xs text-primary-600 mt-1.5">{{ $message }}</p>@enderror
                        </x-form-row>
                    @endunless

                    <x-form-row label="Low stock alert" for="low_stock_threshold" hint="Warn on the dashboard at or below this quantity." x-show="track">
                        <input id="low_stock_threshold" type="number" step="0.001" min="0" name="low_stock_threshold"
                               value="{{ old('low_stock_threshold', $product->low_stock_threshold + 0) }}" class="input" style="max-width:10rem">
                    </x-form-row>
                    <x-checkbox-row label="On sale" name="is_available" :checked="$product->is_available" text="Available at the POS" hint="Untick to hide it without deleting it." />
                </div>
            </section>

            <div class="flex gap-2">
                <button class="btn-primary">{{ $isEdit ? 'Save changes' : 'Add product' }}</button>
                <a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>

        @if ($isEdit)
            <aside class="space-y-6">
                <section class="card overflow-hidden" aria-labelledby="by-wh-heading">
                    <div class="px-5 pt-5 pb-3 flex items-baseline justify-between gap-3">
                        <h2 id="by-wh-heading" class="font-display text-xl">In stock</h2>
                        <span class="font-display text-2xl"><x-qty :value="$product->stock_quantity" :unit="$product->unit?->short_name" /></span>
                    </div>
                    <ul class="divide-y divide-zinc-100 border-t border-zinc-100 text-sm">
                        @forelse ($product->warehouseStocks->sortByDesc('quantity') as $row)
                            <li class="px-5 py-2.5 flex justify-between gap-3">
                                <span class="text-zinc-600">{{ $row->warehouse?->name }}</span>
                                <span class="{{ $row->quantity < 0 ? 'text-primary-600' : '' }}"><x-qty :value="$row->quantity" /></span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-zinc-500">No stock yet. Receive goods with a GRN.</li>
                        @endforelse
                    </ul>
                    <div class="px-5 py-3 border-t border-zinc-100 text-sm flex flex-wrap gap-x-4 gap-y-1">
                        <a href="{{ route('admin.grns.create') }}" class="text-primary-600 font-medium hover:underline underline-offset-4">Receive goods</a>
                        <a href="{{ route('admin.labels.index', ['products' => $product->id]) }}" class="text-primary-600 font-medium hover:underline underline-offset-4">Print barcode labels</a>
                    </div>
                </section>

                <section class="card overflow-hidden" aria-labelledby="moves-heading">
                    <div class="px-5 pt-5 pb-3"><h2 id="moves-heading" class="font-display text-xl">Recent stock changes</h2></div>
                    <ul class="divide-y divide-zinc-100 border-t border-zinc-100 text-sm">
                        @forelse ($movements as $m)
                            <li class="px-5 py-2.5">
                                <div class="flex justify-between gap-3">
                                    <span>{{ $m->typeLabel() }}</span>
                                    <span class="tabular-nums font-medium {{ $m->qty < 0 ? 'text-primary-600' : 'text-emerald-700' }}">{{ $m->qty > 0 ? '+' : '' }}<x-qty :value="$m->qty" /></span>
                                </div>
                                <div class="text-xs text-zinc-500 mt-0.5">{{ $m->created_at->format('d M Y, g:i A') }}{{ $m->warehouse ? ' in '.$m->warehouse->name : '' }}{{ $m->note ? ' - '.$m->note : '' }}</div>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-zinc-500">No stock changes yet.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-semibold">Delete this product</h2>
                    <p class="text-sm text-zinc-500 mt-0.5 mb-3">Only possible if it has never been sold or purchased.</p>
                    <x-delete-button variant="danger" label="Delete product" :action="route('admin.products.destroy', $product)" :confirm="'Delete '.$product->name.'? This can\'t be undone.'" />
                </section>
            </aside>
        @endif
    </div>
</x-layouts.admin>
