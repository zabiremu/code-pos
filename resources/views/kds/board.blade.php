<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kitchen Display</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white min-h-screen p-4"
      x-data="{
          init() {
              // Falls back to polling every 5s when no broadcast connection is
              // configured (see .env's BROADCAST_CONNECTION) — keeps shared-hosting
              // installs working with zero extra setup.
              if (! window.Echo) {
                  setInterval(() => window.location.reload(), 5000);
                  return;
              }
              window.Echo.channel('kitchen-station.{{ $station ?? 'general' }}')
                  .listen('.order-item.status-updated', () => window.location.reload());
          }
      }">
    <h1 class="text-xl font-semibold mb-4">Kitchen Display {{ $station ? '— '.ucfirst($station) : '' }}</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($items as $item)
            @php
                $age = $item->created_at->diffInMinutes(now());
                $ageColor = $age > 15 ? 'border-red-500' : ($age > 7 ? 'border-yellow-500' : 'border-green-500');
            @endphp
            <div class="bg-gray-900 rounded-lg border-l-4 {{ $ageColor }} p-4">
                <div class="flex justify-between text-sm text-gray-400 mb-2">
                    <span>Order #{{ $item->order_id }} &middot; {{ $item->order->table?->label ?? 'Takeaway' }}</span>
                    <span>{{ $age }}m</span>
                </div>
                <p class="font-medium">{{ $item->quantity }}× {{ $item->menuItem->name }}</p>
                @if ($item->modifiers->count())
                    <p class="text-xs text-gray-400">{{ $item->modifiers->pluck('name')->join(', ') }}</p>
                @endif
                @if ($item->notes)
                    <p class="text-xs text-yellow-400 italic">{{ $item->notes }}</p>
                @endif

                <form method="POST" action="{{ route('kds.tickets.bump', $item) }}" class="mt-3">
                    @csrf
                    <button class="w-full bg-white text-gray-900 text-sm rounded px-3 py-2">
                        {{ match($item->status) { 'sent' => 'Start preparing', 'preparing' => 'Mark ready', 'ready' => 'Mark served', default => 'Update' } }}
                    </button>
                </form>
            </div>
        @endforeach

        @if ($items->isEmpty())
            <p class="text-gray-500 col-span-full text-center py-12">No tickets right now.</p>
        @endif
    </div>
</body>
</html>
