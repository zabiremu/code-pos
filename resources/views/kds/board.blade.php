<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kitchen Display &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-950 text-white min-h-screen"
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
    <header class="bg-primary-900/40 border-b border-primary-900/60 px-5 py-3 flex items-center justify-between sticky top-0 z-10 backdrop-blur">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <path d="M4 3v7a3 3 0 0 0 3 3v8M4 3v4M7 3v7M4 7h3M10 3c-1.5 2-1.5 6 0 8v10" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3c-1.7 0-3 2.24-3 5s1.3 5 3 5v8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 class="text-lg font-semibold">Kitchen Display{{ $station ? ' — '.ucfirst($station) : '' }}</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-zinc-400 hover:text-white transition-colors">Exit</a>
    </header>

    <div class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($items as $item)
                @php
                    $age = $item->created_at->diffInMinutes(now());
                    $ageColor = $age > 15 ? 'border-primary-500' : ($age > 7 ? 'border-yellow-500' : 'border-green-500');
                    $ageBadge = $age > 15 ? 'bg-primary-600' : ($age > 7 ? 'bg-yellow-600' : 'bg-zinc-700');
                @endphp
                <div class="bg-zinc-900 rounded-xl border-l-4 {{ $ageColor }} p-4 shadow-lg">
                    <div class="flex justify-between items-center text-sm text-zinc-400 mb-2">
                        <span>Order #{{ $item->order_id }} &middot; {{ $item->order->table?->label ?? 'Takeaway' }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $ageBadge }} text-white">{{ $age }}m</span>
                    </div>
                    <p class="font-semibold text-base">{{ $item->quantity }}× {{ $item->menuItem->name }}</p>
                    @if ($item->modifiers->count())
                        <p class="text-xs text-zinc-400 mt-1">{{ $item->modifiers->pluck('name')->join(', ') }}</p>
                    @endif
                    @if ($item->notes)
                        <p class="text-xs text-yellow-400 italic mt-1">{{ $item->notes }}</p>
                    @endif

                    <form method="POST" action="{{ route('kds.tickets.bump', $item) }}" class="mt-3">
                        @csrf
                        <button class="w-full bg-white text-zinc-900 text-sm font-medium rounded-lg px-3 py-2 hover:bg-zinc-100 transition-colors">
                            {{ match($item->status) { 'sent' => 'Start preparing', 'preparing' => 'Mark ready', 'ready' => 'Mark served', default => 'Update' } }}
                        </button>
                    </form>
                </div>
            @endforeach

            @if ($items->isEmpty())
                <p class="text-zinc-500 col-span-full text-center py-16">No tickets right now.</p>
            @endif
        </div>
    </div>
</body>
</html>
