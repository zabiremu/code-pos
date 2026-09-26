{{-- Same list-table shape as Sales: status tabs, search, table, count + pagination. --}}
<x-layouts.admin title="Suppliers">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-sm text-zinc-500">The people and companies you buy stock from.</p>
        <a href="{{ route('admin.suppliers.create') }}" class="btn-primary shrink-0">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            Add supplier
        </a>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 mb-4">
        <nav class="flex items-center gap-1 text-sm" aria-label="Filter by status">
            @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                <a href="{{ route('admin.suppliers.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}"
                   @if ($status === $key) aria-current="page" @endif
                   class="px-3 py-2 -mb-px border-b-2 transition-colors {{ $status === $key ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-800' }}">
                    {{ $label }} <span class="text-zinc-400">({{ $counts[$key] }})</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.suppliers.index') }}" class="flex items-center gap-2 pb-2" role="search">
            @if ($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Name, phone or email" aria-label="Search suppliers" class="input w-64">
            <button class="btn-secondary">Search</button>
        </form>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">VAT / BIN</th>
                    <th class="px-5 py-3 font-medium text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($suppliers as $supplier)
                    <tr class="group hover:bg-primary-50/40 transition-colors {{ $supplier->is_active ? '' : 'text-zinc-400' }}">
                        <td class="px-5 py-3 align-top">
                            <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="font-medium {{ $supplier->is_active ? 'text-zinc-900' : 'text-zinc-500' }} hover:text-primary-600">
                                {{ $supplier->displayName() }}
                            </a>
                            @if ($supplier->company_name)
                                <div class="text-xs text-zinc-500 mt-0.5">{{ $supplier->name }}</div>
                            @endif
                            <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="text-zinc-500 hover:text-primary-600">Edit</a>
                                <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}"
                                      onsubmit="return confirm('Delete {{ addslashes($supplier->displayName()) }}? This can\'t be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-zinc-500 hover:text-primary-600">Delete</button>
                                </form>
                            </div>
                        </td>
                        <td class="px-5 py-3 align-top whitespace-nowrap">
                            @if ($supplier->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supplier->phone) }}" class="hover:text-primary-600">{{ $supplier->phone }}</a>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 align-top">
                            @if ($supplier->email)
                                <a href="mailto:{{ $supplier->email }}" class="hover:text-primary-600">{{ $supplier->email }}</a>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 align-top text-zinc-600">{{ $supplier->tax_number ?: '—' }}</td>
                        <td class="px-5 py-3 align-top text-right">
                            <span class="{{ $supplier->is_active ? 'badge-green' : 'badge-gray' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center">
                            @if ($search !== '')
                                <p class="text-zinc-600">No suppliers match "{{ $search }}".</p>
                                <a href="{{ route('admin.suppliers.index', array_filter(['status' => $status === 'all' ? null : $status])) }}" class="text-sm text-primary-600 hover:underline underline-offset-4 mt-1 inline-block">Clear search</a>
                            @else
                                <p class="text-zinc-600">No suppliers yet.</p>
                                <a href="{{ route('admin.suppliers.create') }}" class="text-sm text-primary-600 hover:underline underline-offset-4 mt-1 inline-block">Add your first supplier</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-3 text-xs text-zinc-500">
        <span>{{ $suppliers->total() }} {{ $suppliers->total() === 1 ? 'supplier' : 'suppliers' }}</span>
        {{ $suppliers->links() }}
    </div>
</x-layouts.admin>
