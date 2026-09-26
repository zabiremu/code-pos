<x-layouts.admin title="Add supplier">
    <div class="mb-5">
        <a href="{{ route('admin.suppliers.index') }}" class="text-sm text-zinc-500 hover:text-primary-600">&larr; All suppliers</a>
    </div>

    <div class="card max-w-3xl">
        <form method="POST" action="{{ route('admin.suppliers.store') }}">
            @csrf
            @include('admin.suppliers._form')
            <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
                <button class="btn-primary">Add supplier</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
