{{--
    Shared sidebar content, rendered by both the always-visible desktop
    aside and the mobile slide-over drawer in components/layouts/admin.blade.php
    - kept in one place so the two never drift out of sync.
--}}
@php
    $user = auth()->user();
    $isAdminOrManager = $user?->hasAnyRole(['admin', 'manager']);
@endphp

<div class="px-5 py-5 flex items-center gap-2 border-b border-white/10">
    <svg class="w-7 h-7 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
        <path d="M4 3v7a3 3 0 0 0 3 3v8M4 3v4M7 3v7M4 7h3M10 3c-1.5 2-1.5 6 0 8v10" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M16 3c-1.7 0-3 2.24-3 5s1.3 5 3 5v8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <div class="leading-tight">
        <p class="font-semibold text-white">{{ config('app.name') }}</p>
        <p class="text-[11px] text-primary-200 uppercase tracking-wide">Point of Sale</p>
    </div>
</div>

<nav class="flex-1 px-3 py-4 space-y-4 text-sm overflow-y-auto">
    @if ($isAdminOrManager)
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : 'nav-link' }}">
            Dashboard
        </a>
    @endif

    @if ($user?->hasAnyRole(['admin', 'manager', 'waiter', 'cashier']))
        <a href="{{ route('pos.orders.index') }}" class="{{ request()->routeIs('pos.*') ? 'nav-link-active' : 'nav-link' }}">
            Orders
        </a>
    @endif

    @if ($user?->hasAnyRole(['admin', 'manager', 'kitchen']))
        <a href="{{ route('kds.tickets.index') }}" class="{{ request()->routeIs('kds.*') ? 'nav-link-active' : 'nav-link' }}">
            Kitchen Display
        </a>
    @endif

    @if ($isAdminOrManager)
        <div>
            <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wide text-primary-300">Menu</p>
            <div class="space-y-1">
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'nav-link-active' : 'nav-link' }}">Categories</a>
                <a href="{{ route('admin.menu-items.index') }}" class="{{ request()->routeIs('admin.menu-items.*') ? 'nav-link-active' : 'nav-link' }}">Menu Items</a>
                <a href="{{ route('admin.modifier-groups.index') }}" class="{{ request()->routeIs('admin.modifier-groups.*') ? 'nav-link-active' : 'nav-link' }}">Modifier Groups</a>
            </div>
        </div>

        <div>
            <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wide text-primary-300">Floor</p>
            <div class="space-y-1">
                <a href="{{ route('admin.floors.index') }}" class="{{ request()->routeIs('admin.floors.*') ? 'nav-link-active' : 'nav-link' }}">Floors</a>
                <a href="{{ route('admin.tables.index') }}" class="{{ request()->routeIs('admin.tables.*') ? 'nav-link-active' : 'nav-link' }}">Tables</a>
            </div>
        </div>

        <div>
            <p class="px-3 mb-1 text-[11px] font-semibold uppercase tracking-wide text-primary-300">Manage</p>
            <div class="space-y-1">
                <a href="{{ route('admin.staff.index') }}" class="{{ request()->routeIs('admin.staff.*') ? 'nav-link-active' : 'nav-link' }}">Staff</a>
                <a href="{{ route('admin.reports.sales') }}" class="{{ request()->routeIs('admin.reports.sales') ? 'nav-link-active' : 'nav-link' }}">Sales Report</a>
                <a href="{{ route('admin.reports.low-stock') }}" class="{{ request()->routeIs('admin.reports.low-stock') ? 'nav-link-active' : 'nav-link' }}">Low Stock</a>
            </div>
        </div>
    @endif
</nav>

<div class="px-3 py-3 border-t border-white/10">
    <div class="flex items-center gap-2 px-2 py-2">
        <span class="w-8 h-8 rounded-full bg-white text-primary-700 font-semibold text-xs flex items-center justify-center shrink-0">
            {{ strtoupper(substr($user?->name ?? '?', 0, 1)) }}
        </span>
        <div class="min-w-0">
            <p class="text-sm text-white truncate">{{ $user?->name }}</p>
            <p class="text-xs text-primary-300 truncate">{{ $user?->roles->pluck('name')->map(fn ($r) => ucfirst($r))->join(', ') }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full text-left px-2 py-2 rounded-lg text-sm text-primary-200 hover:bg-white/10 hover:text-white transition-colors">
            Log out
        </button>
    </form>
</div>
