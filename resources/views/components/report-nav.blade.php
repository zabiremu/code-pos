{{-- Tabs across the report pages + the period picker. Pass :range to show the picker. --}}
@props(['range' => null])
@php
    $tabs = [
        'admin.reports.sales' => 'Sales',
        'admin.reports.profit-loss' => 'Profit & loss',
        'admin.reports.purchases' => 'Purchases',
        'admin.reports.stock-value' => 'Stock value',
        'admin.reports.low-stock' => 'Low stock',
    ];
    $keep = $range ? $range->query() : [];
@endphp
<div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 mb-6 print:hidden">
    <nav class="flex items-center gap-1 text-sm overflow-x-auto" aria-label="Reports">
        @foreach ($tabs as $route => $label)
            <x-tab-link :href="route($route, in_array($route, ['admin.reports.stock-value', 'admin.reports.low-stock']) ? [] : $keep)" :active="request()->routeIs($route)">{{ $label }}</x-tab-link>
        @endforeach
    </nav>
    @if ($range)
        <form method="GET" class="flex flex-wrap items-center gap-2 pb-2" x-data="{ preset: @js($range->preset) }">
            <select name="range" x-model="preset" class="input w-auto" aria-label="Period" @change="preset !== 'custom' && $el.form.submit()">
                @foreach (\App\Support\DateRange::PRESETS as $key => $label)
                    <option value="{{ $key }}" @selected($range->preset === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <template x-if="preset === 'custom'">
                <span class="flex items-center gap-2">
                    <input type="date" name="from" value="{{ $range->from->toDateString() }}" class="input w-auto" aria-label="From">
                    <span class="text-zinc-400">to</span>
                    <input type="date" name="to" value="{{ $range->to->toDateString() }}" class="input w-auto" aria-label="To">
                    <button class="btn-secondary">Apply</button>
                </span>
            </template>
            <button type="button" onclick="window.print()" class="btn-secondary" title="Print this report">Print</button>
        </form>
    @endif
</div>
@if ($range)
    <p class="text-sm text-zinc-500 -mt-3 mb-5">{{ $range->label() }}</p>
@endif
