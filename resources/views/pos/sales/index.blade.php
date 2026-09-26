{{--
    Structured like a WordPress admin list-table screen (Posts/Pages):
    status tabs with counts, a search box above the table, a real <table>
    with a header row and a hover-revealed "row action" under the title
    cell instead of the whole row being one big link, zebra striping,
    and an item count next to pagination.
--}}
<x-layouts.admin :title="'Sales'">
    <div class="flex items-center justify-between gap-3 mb-4">
        <p class="text-sm text-zinc-500">Sales currently open or billed, awaiting payment.</p>
        <form method="POST" action="{{ route('pos.sales.store') }}">
            @csrf
            <button class="btn-primary shrink-0">New sale</button>
        </form>
    </div>

    {{-- Status tabs --}}
    <div class="flex items-center gap-1 text-sm border-b border-zinc-200 mb-4">
        @php
            $tabs = [
                'all' => 'All',
                'open' => 'Open',
                'billed' => 'Billed',
            ];
        @endphp
        @foreach ($tabs as $key => $label)
            <a href="{{ route('pos.sales.index', array_filter(['status' => $key === 'all' ? null : $key, 'q' => $search ?: null])) }}"
               class="px-3 py-2 -mb-px border-b-2 transition-colors {{ $status === $key ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-800' }}">
                {{ $label }} <span class="text-zinc-400">({{ $counts[$key] }})</span>
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="flex justify-end mb-3">
        <form method="GET" action="{{ route('pos.sales.index') }}" class="flex items-center gap-2">
            @if ($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <input type="text" name="q" value="{{ $search }}" placeholder="Search sale #&hellip;" class="input w-56">
            <button class="btn-secondary">Search</button>
        </form>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 border-b border-zinc-100">
                    <th class="px-5 py-3">Sale</th>
                    <th class="px-5 py-3">Items</th>
                    <th class="px-5 py-3">Cashier</th>
                    <th class="px-5 py-3 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($sales as $sale)
                    @php
                        $statusBadge = match ($sale->status) {
                            'open' => 'badge-gray',
                            'billed' => 'badge-dark',
                            'closed' => 'badge-green',
                            default => 'badge-gray',
                        };
                    @endphp
                    <tr class="group even:bg-zinc-50/60 hover:bg-primary-50/40 transition-colors">
                        <td class="px-5 py-3 align-top">
                            <a href="{{ route('pos.sales.show', $sale) }}" class="font-medium text-zinc-900 hover:text-primary-600">
                                #{{ $sale->id }}
                            </a>
                            <div class="text-xs text-zinc-400 mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('pos.sales.show', $sale) }}" class="hover:text-primary-600">View</a>
                            </div>
                        </td>
                        <td class="px-5 py-3 align-top text-zinc-600">{{ $sale->items->count() }}</td>
                        <td class="px-5 py-3 align-top text-zinc-600">{{ $sale->cashier?->name ?? '—' }}</td>
                        <td class="px-5 py-3 align-top text-right">
                            <span class="{{ $statusBadge }}">{{ ucfirst($sale->status) }}</span>
                        </td>
                    </tr>
                @endforeach

                @if ($sales->isEmpty())
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-sm text-zinc-400 text-center">
                            {{ $search !== '' ? 'No sales match your search.' : 'No sales in this view right now.' }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-3 text-xs text-zinc-500">
        <span>{{ $sales->total() }} {{ $sales->total() === 1 ? 'item' : 'items' }}</span>
        {{ $sales->links() }}
    </div>
</x-layouts.admin>
