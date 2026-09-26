<x-layouts.admin :title="'Sales Report'">
    <form method="GET" class="mb-4">
        <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="input">
    </form>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="card p-5">
            <p class="text-sm text-zinc-500">Total sales</p>
            <p class="text-3xl font-semibold mt-1">{{ number_format($totalSales, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-zinc-500">Bills paid</p>
            <p class="text-3xl font-semibold mt-1">{{ $billCount }}</p>
        </div>
    </div>

    <div class="card divide-y">
        <p class="px-5 py-3 font-medium text-sm">Top items</p>
        @foreach ($topItems as $row)
            <div class="flex justify-between px-5 py-2 text-sm">
                <span>{{ $row->menuItem->name }}</span>
                <span>{{ $row->qty }} sold</span>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
