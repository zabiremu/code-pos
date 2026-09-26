<x-layouts.admin :title="$supplier->displayName()">
    <div class="mb-5">
        <a href="{{ route('admin.suppliers.index') }}" class="text-sm text-zinc-500 hover:text-primary-600">&larr; All suppliers</a>
    </div>

    <div class="card max-w-3xl">
        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')
            @include('admin.suppliers._form')
            <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
                <button class="btn-primary">Save changes</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card max-w-3xl mt-6 p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold">Delete this supplier</h2>
            <p class="text-sm text-zinc-500 mt-0.5">Removes them for good. To keep their details, mark them inactive instead.</p>
        </div>
        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}"
              onsubmit="return confirm('Delete {{ addslashes($supplier->displayName()) }}? This can\'t be undone.')">
            @csrf
            @method('DELETE')
            <button class="btn bg-white text-primary-700 ring-1 ring-inset ring-primary-200 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500">Delete supplier</button>
        </form>
    </div>
</x-layouts.admin>
