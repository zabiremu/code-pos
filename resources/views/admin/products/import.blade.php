<x-layouts.admin title="Import products">
    <x-back-link :href="route('admin.products.index')">All products</x-back-link>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <form method="POST" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data" class="card xl:col-span-2">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <h2 class="text-base font-semibold">Upload a CSV file</h2>
                    <p class="text-sm text-zinc-500 mt-1">Add many products at once, or update existing ones. Rows whose SKU matches a product update it; everything else is added as new.</p>
                </div>
                <input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-700">
                @error('file')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror
                @if (session('importErrors'))
                    <ul class="rounded-xl bg-primary-50 ring-1 ring-primary-600/15 p-4 text-sm text-primary-900 space-y-1 max-h-72 overflow-y-auto">
                        @foreach (session('importErrors') as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                @endif
            </div>
            <div class="px-5 py-4 border-t border-zinc-100 flex flex-wrap gap-2">
                <button class="btn-primary">Import</button>
                <a href="{{ route('admin.products.import.template') }}" class="btn-secondary">Download template</a>
                <a href="{{ route('admin.products.export') }}" class="btn-secondary">Export current products</a>
            </div>
        </form>

        <aside class="card p-5 text-sm space-y-3">
            <h2 class="font-display text-xl">How it works</h2>
            <ul class="list-disc pl-5 space-y-1.5 text-zinc-600">
                <li><strong class="text-zinc-800">name</strong> and <strong class="text-zinc-800">sale_price</strong> are required. Every other column is optional.</li>
                <li>New categories, brands and units are created automatically.</li>
                <li><strong class="text-zinc-800">opening_stock</strong> only applies to new products and goes into the default warehouse. To change stock later, use a GRN or a stock adjustment.</li>
                <li>When updating, a blank cell keeps the current value.</li>
                <li>Yes/no columns accept yes, no, 1, 0, true or false.</li>
                <li>If any row has a problem, nothing is imported and every problem is listed.</li>
                <li>In Excel, save as <strong class="text-zinc-800">CSV UTF-8</strong> so Bangla names come through correctly.</li>
            </ul>
            <p class="text-xs text-zinc-500 pt-2">Columns: <span class="font-mono">{{ implode(', ', $columns) }}</span></p>
        </aside>
    </div>
</x-layouts.admin>
