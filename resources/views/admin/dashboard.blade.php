{{--
    Dashboard. The revenue card is the one bold element: today's takings as a
    heartbeat line across the hours of the day, on the same vampire-blood
    surface as the sidebar and sign-in page. Everything else stays quiet.
--}}
@php
    // Revenue line: x = hour of day (0-23), y = revenue that hour. Hours after
    // "now" aren't drawn, so the line stops where the day currently is.
    $w = 720; $h = 150; $pad = 12;
    $max = max(max($hourlyRevenue), 1);
    $x = fn (int $hour) => round($hour / 23 * $w, 1);
    $y = fn (float $v) => round($h - $pad - ($v / $max) * ($h - 2 * $pad), 1);
    $points = collect(range(0, $currentHour))->map(fn ($hr) => $x($hr).' '.$y($hourlyRevenue[$hr]));
    $path = 'M'.$points->implode(' L');
    $nowX = $x($currentHour);
    $nowY = $y($hourlyRevenue[$currentHour]);

    $money = fn ($v) => number_format((float) $v, 2);
    $delta = $yesterdaySoFar > 0 ? (($todayRevenue - $yesterdaySoFar) / $yesterdaySoFar) * 100 : null;

    $statusBadge = fn (string $status) => match ($status) {
        'open' => 'badge-gray',
        'billed' => 'badge-dark',
        'closed' => 'badge-green',
        default => 'badge-gray',
    };
@endphp
<x-layouts.admin :title="'Dashboard'">
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Revenue today --}}
        <section class="xl:col-span-2 rounded-2xl sidebar-surface text-bone p-6 md:p-8 overflow-hidden relative" aria-labelledby="revenue-heading">
            <div class="flex flex-wrap items-start justify-between gap-4 relative">
                <div>
                    <h2 id="revenue-heading" class="text-sm text-bone/70">Revenue today{{ $currency ? ' ('.$currency.')' : '' }}</h2>
                    <p class="font-display text-5xl md:text-6xl leading-none mt-3 tabular-nums">{{ $money($todayRevenue) }}</p>
                    <p class="text-sm text-bone/60 mt-3">
                        @if ($delta === null)
                            No paid bills yesterday by this time.
                        @else
                            {{ $delta >= 0 ? 'Up' : 'Down' }} {{ number_format(abs($delta), 0) }}% on yesterday by this time ({{ $money($yesterdaySoFar) }}).
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('pos.sales.store') }}">
                    @csrf
                    <button class="btn bg-bone text-primary-700 hover:bg-white shadow-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brass focus-visible:ring-offset-2 focus-visible:ring-offset-primary-600">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                        New sale
                    </button>
                </form>
            </div>

            <div class="mt-8 relative">
                <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-32 md:h-40 overflow-visible" preserveAspectRatio="none"
                     role="img" aria-label="Revenue by hour today, up to {{ now()->format('g A') }}">
                    <line x1="0" y1="{{ $h - $pad }}" x2="{{ $w }}" y2="{{ $h - $pad }}" stroke="rgba(243,236,236,.18)" stroke-dasharray="2 6" vector-effect="non-scaling-stroke"/>
                    <path d="{{ $path }}" pathLength="1" class="pulse-draw" fill="none" stroke="#F3ECEC" stroke-width="2.5"
                          stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"
                          style="filter: drop-shadow(0 0 6px rgba(255, 90, 100, .5))"/>
                    <line x1="{{ $nowX }}" y1="0" x2="{{ $nowX }}" y2="{{ $h - $pad }}" class="pulse-now" stroke="rgba(196,154,90,.45)" stroke-width="1" vector-effect="non-scaling-stroke"/>
                </svg>
                {{-- "Now" dot as HTML so it stays round under preserveAspectRatio="none". --}}
                <span class="pulse-now absolute w-2.5 h-2.5 rounded-full bg-brass ring-4 ring-brass/25 -translate-x-1/2 -translate-y-1/2"
                      style="left: {{ $nowX / $w * 100 }}%; top: {{ $nowY / $h * 100 }}%"></span>
                <div class="flex justify-between text-xs text-bone/45 mt-2 tabular-nums" aria-hidden="true">
                    <span>12 AM</span><span>6 AM</span><span>12 PM</span><span>6 PM</span><span>11 PM</span>
                </div>
            </div>
        </section>

        {{-- At a glance --}}
        <section class="card divide-y divide-zinc-100" aria-label="At a glance">
            <div class="p-5 flex items-baseline justify-between gap-4">
                <div>
                    <p class="text-sm text-zinc-500">Sales started today</p>
                    <p class="font-display text-4xl mt-1 tabular-nums">{{ $todaySales }}</p>
                </div>
            </div>
            <a href="{{ route('pos.sales.index') }}" class="p-5 flex items-baseline justify-between gap-4 hover:bg-primary-50/50 transition-colors group">
                <div>
                    <p class="text-sm text-zinc-500">Open right now</p>
                    <p class="font-display text-4xl mt-1 tabular-nums">{{ $openSales }}</p>
                </div>
                <span class="text-sm font-medium text-primary-600 group-hover:underline underline-offset-4">View sales</span>
            </a>
            <a href="{{ route('admin.reports.low-stock') }}" class="p-5 flex items-baseline justify-between gap-4 hover:bg-primary-50/50 transition-colors group">
                <div>
                    <p class="text-sm text-zinc-500">Low on stock</p>
                    <p class="font-display text-4xl mt-1 tabular-nums {{ $lowStockCount > 0 ? 'text-primary-600' : '' }}">{{ $lowStockCount }}</p>
                </div>
                <span class="text-sm font-medium text-primary-600 group-hover:underline underline-offset-4">Restock list</span>
            </a>
        </section>

        {{-- Recent sales --}}
        <section class="card xl:col-span-2 overflow-hidden" aria-labelledby="recent-heading">
            <div class="px-5 pt-5 pb-3 flex items-center justify-between">
                <h2 id="recent-heading" class="font-display text-xl">Recent sales</h2>
                <a href="{{ route('pos.sales.index') }}" class="text-sm font-medium text-primary-600 hover:underline underline-offset-4">All sales</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-y border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-2.5 font-medium">Sale</th>
                            <th class="px-5 py-2.5 font-medium">Items</th>
                            <th class="px-5 py-2.5 font-medium">Cashier</th>
                            <th class="px-5 py-2.5 font-medium">Started</th>
                            <th class="px-5 py-2.5 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($recentSales as $sale)
                            <tr class="hover:bg-primary-50/40 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('pos.sales.show', $sale) }}" class="font-medium text-zinc-900 hover:text-primary-600">#{{ $sale->id }}</a>
                                </td>
                                <td class="px-5 py-3 text-zinc-600 tabular-nums">{{ $sale->items_count }}</td>
                                <td class="px-5 py-3 text-zinc-600">{{ $sale->cashier?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-zinc-500">{{ $sale->created_at->diffForHumans() }}</td>
                                <td class="px-5 py-3 text-right"><span class="{{ $statusBadge($sale->status) }}">{{ ucfirst($sale->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-zinc-500">
                                    No sales yet. Start one with the New sale button above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Running low --}}
        <section class="card" aria-labelledby="low-heading">
            <div class="px-5 pt-5 pb-3 flex items-center justify-between">
                <h2 id="low-heading" class="font-display text-xl">Running low</h2>
                @if ($lowStockCount > 5)
                    <a href="{{ route('admin.reports.low-stock') }}" class="text-sm font-medium text-primary-600 hover:underline underline-offset-4">See all {{ $lowStockCount }}</a>
                @endif
            </div>
            <ul class="divide-y divide-zinc-100 border-t border-zinc-100">
                @forelse ($lowStockProducts as $product)
                    @php $ratio = $product->low_stock_threshold > 0 ? min($product->stock_quantity / $product->low_stock_threshold, 1) : 0; @endphp
                    <li class="px-5 py-3">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm text-zinc-800 truncate">{{ $product->name }}</span>
                            <span class="text-sm tabular-nums {{ $product->stock_quantity <= 0 ? 'text-primary-600 font-semibold' : 'text-zinc-600' }}">
                                {{ $product->stock_quantity <= 0 ? 'Out' : $product->stock_quantity.' left' }}
                            </span>
                        </div>
                        <div class="mt-2 h-1 rounded-full bg-primary-100 overflow-hidden" aria-hidden="true">
                            <div class="h-full rounded-full bg-primary-600" style="width: {{ max($ratio * 100, 3) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-zinc-500">Everything is above its restock level.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
