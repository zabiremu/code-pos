{{-- Intro line + primary "add" button above a list, then a search box. --}}
@props(['intro', 'createRoute' => null, 'createLabel' => null, 'search' => '', 'placeholder' => 'Search', 'action'])
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <p class="text-sm text-zinc-500">{{ $intro }}</p>
    @if ($createRoute)
        <a href="{{ $createRoute }}" class="btn-primary shrink-0">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            {{ $createLabel }}
        </a>
    @endif
</div>
<div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 mb-4">
    <div class="flex items-center gap-1 text-sm">{{ $slot }}</div>
    <form method="GET" action="{{ $action }}" class="flex items-center gap-2 pb-2" role="search">
        {{ $hidden ?? '' }}
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}" class="input w-60">
        <button class="btn-secondary">Search</button>
    </form>
</div>
