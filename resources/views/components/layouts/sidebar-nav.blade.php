{{--
    Shared sidebar content, rendered by both the always-visible desktop
    aside and the mobile slide-over drawer in components/layouts/admin.blade.php
    - kept in one place so the two never drift out of sync. Sits on the
    vampire-blood .sidebar-surface, so text here is bone, not zinc.
--}}
@php
    $user = auth()->user();
    $isAdminOrManager = $user?->hasAnyRole(['admin', 'manager']);
    $link = fn (string $pattern) => request()->routeIs($pattern) ? 'nav-link-active' : 'nav-link';
    $current = fn (string $pattern) => request()->routeIs($pattern) ? 'aria-current=page' : '';
@endphp

<div class="px-5 pt-6 pb-5 flex items-center gap-3">
    <span class="w-9 h-9 rounded-[10px] ring-1 ring-brass/80 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-bone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M3 13h4l2-6 4 11 2-5h6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <p class="font-display text-lg leading-tight text-bone truncate">{{ config('app.name') }}</p>
</div>

<nav class="flex-1 px-3 py-2 space-y-6 text-sm overflow-y-auto" aria-label="Main">
    <div class="space-y-1">
        @if ($isAdminOrManager)
            <a href="{{ route('admin.dashboard') }}" class="{{ $link('admin.dashboard') }}" {{ $current('admin.dashboard') }}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 13h4l2-6 4 11 2-5h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Dashboard
            </a>
        @endif

        @if ($user?->hasAnyRole(['admin', 'manager', 'cashier']))
            <a href="{{ route('pos.sales.index') }}" class="{{ $link('pos.*') }}" {{ $current('pos.*') }}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" stroke-linejoin="round"/><path d="M9 8h6M9 12h6" stroke-linecap="round"/></svg>
                Sales
            </a>
        @endif
    </div>

    @if ($isAdminOrManager)
        <div>
            <p class="px-3 mb-2 text-xs font-medium text-bone/40">Catalog</p>
            <div class="space-y-1">
                <a href="{{ route('admin.categories.index') }}" class="{{ $link('admin.categories.*') }}" {{ $current('admin.categories.*') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 5h7v7H4zM13 5h7v7h-7zM4 14h7v5H4zM13 14h7v5h-7z" stroke-linejoin="round"/></svg>
                    Categories
                </a>
                <a href="{{ route('admin.products.index') }}" class="{{ $link('admin.products.*') }}" {{ $current('admin.products.*') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M3 8l9-5 9 5-9 5-9-5Z" stroke-linejoin="round"/><path d="M3 8v8l9 5 9-5V8M12 13v8" stroke-linejoin="round"/></svg>
                    Products
                </a>
                <a href="{{ route('admin.suppliers.index') }}" class="{{ $link('admin.suppliers.*') }}" {{ $current('admin.suppliers.*') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M2 6h11v10H2zM13 9h4.5l3.5 3.5V16h-8" stroke-linejoin="round"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                    Suppliers
                </a>
            </div>
        </div>

        <div>
            <p class="px-3 mb-2 text-xs font-medium text-bone/40">Manage</p>
            <div class="space-y-1">
                <a href="{{ route('admin.staff.index') }}" class="{{ $link('admin.staff.*') }}" {{ $current('admin.staff.*') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.5 3.4-5.5 6.5-5.5s5.7 2 6.5 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c1.9.7 3.1 2.5 3.5 5.2" stroke-linecap="round"/></svg>
                    Staff
                </a>
                <a href="{{ route('admin.shifts.index') }}" class="{{ $link('admin.shifts.*') }}" {{ $current('admin.shifts.*') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Attendance
                </a>
                <a href="{{ route('admin.reports.sales') }}" class="{{ $link('admin.reports.sales') }}" {{ $current('admin.reports.sales') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" stroke-linecap="round"/></svg>
                    Sales report
                </a>
                <a href="{{ route('admin.reports.low-stock') }}" class="{{ $link('admin.reports.low-stock') }}" {{ $current('admin.reports.low-stock') }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M12 4 2.5 20h19L12 4Z" stroke-linejoin="round"/><path d="M12 10v4.5M12 17.5h.01" stroke-linecap="round"/></svg>
                    Low stock
                </a>
                @if ($user?->hasRole('admin'))
                    <a href="{{ route('admin.settings.edit') }}" class="{{ $link('admin.settings.*') }}" {{ $current('admin.settings.*') }}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z" stroke-linejoin="round"/></svg>
                        Settings
                    </a>
                @endif
            </div>
        </div>
    @endif
</nav>

<div class="px-3 py-3 border-t border-white/10">
    <div class="flex items-center gap-3 px-2 py-2">
        <span class="w-9 h-9 rounded-full bg-bone text-primary-700 font-semibold text-sm flex items-center justify-center shrink-0">
            {{ strtoupper(substr($user?->name ?? '?', 0, 1)) }}
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm text-bone truncate">{{ $user?->name }}</p>
            <p class="text-xs text-bone/50 truncate">{{ $user?->roles->pluck('name')->map(fn ($r) => ucfirst($r))->join(', ') }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Log out" aria-label="Log out"
                    class="p-2 rounded-lg text-bone/60 hover:text-bone hover:bg-white/5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brass">
                <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l4-4-4-4M14 12H4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </form>
    </div>
</div>
