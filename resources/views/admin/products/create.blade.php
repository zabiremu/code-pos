<x-layouts.admin :title="'Add Product'">
    <form method="POST" action="{{ route('admin.products.store') }}" class="card p-6 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="field-label">Name</label>
            <input type="text" name="name" required class="w-full input">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="field-label">Category</label>
                <select name="category_id" class="w-full input">
                    <option value="">Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Price</label>
                <input type="number" step="0.01" name="base_price" required class="w-full input">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="field-label">SKU (optional)</label>
                <input type="text" name="sku" class="w-full input">
            </div>
            <div>
                <label class="field-label">Tax rate % (optional)</label>
                <input type="number" step="0.01" name="tax_rate" class="w-full input">
            </div>
        </div>

        <div>
            <label class="field-label">Description</label>
            <textarea name="description" rows="3" class="w-full input"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 items-end">
            <label class="text-sm flex items-center gap-2">
                <input type="checkbox" name="track_stock" value="1">
                Track stock for this product
            </label>
            <div>
                <label class="field-label">Starting stock quantity</label>
                <input type="number" step="0.001" name="stock_quantity" value="0" class="w-full input">
            </div>
        </div>

        <div>
            <label class="field-label">Low stock threshold</label>
            <input type="number" step="0.001" name="low_stock_threshold" value="0" class="w-full input">
        </div>

        <label class="text-sm flex items-center gap-2">
            <input type="checkbox" name="is_available" value="1" checked>
            Available for sale
        </label>

        <button class="btn-primary">Save product</button>
    </form>
</x-layouts.admin>
