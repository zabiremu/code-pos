<x-layouts.admin :title="'Dashboard'">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-sm text-zinc-500">Sales today</p>
            <p class="text-3xl font-semibold mt-1">{{ $todaySales }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-zinc-500">Currently open</p>
            <p class="text-3xl font-semibold mt-1">{{ $openSales }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-zinc-500">Revenue today</p>
            <p class="text-3xl font-semibold mt-1">{{ number_format($todayRevenue, 2) }}</p>
        </div>
        <a href="{{ route('admin.reports.low-stock') }}" class="card p-5 hover:border-primary-200 hover:shadow transition-all">
            <p class="text-sm text-zinc-500">Low stock items</p>
            <p class="text-3xl font-semibold mt-1 {{ $lowStockCount > 0 ? 'text-primary-600' : '' }}">{{ $lowStockCount }}</p>
        </a>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <form method="POST" action="{{ route('pos.sales.store') }}">
            @csrf
            <button class="btn-primary">New sale</button>
        </form>
        <a href="{{ route('pos.sales.index') }}" class="btn-secondary">View sales</a>
        <a href="{{ route('admin.reports.sales') }}" class="btn-secondary">Sales report</a>
    </div>
</x-layouts.admin>
