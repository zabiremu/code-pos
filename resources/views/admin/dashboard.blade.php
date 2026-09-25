<x-layouts.admin :title="'Dashboard'">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm text-gray-500">Orders today</p>
            <p class="text-3xl font-semibold mt-1">{{ $todayOrders }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm text-gray-500">Currently open</p>
            <p class="text-3xl font-semibold mt-1">{{ $openOrders }}</p>
        </div>
    </div>
</x-layouts.admin>
