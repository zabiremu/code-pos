{{-- Full-screen shell for the register and its receipt: no admin sidebar, just a slim blood top bar. --}}
@props(['title' => 'Register'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} &middot; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gloock&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-bone text-zinc-900 antialiased font-sans">
    <div class="h-full flex flex-col">
        <header class="sidebar-surface text-bone h-14 shrink-0 flex items-center gap-3 px-3 sm:px-4 print:hidden">
            <a href="{{ route('pos.register') }}" class="flex items-center gap-2.5 min-w-0 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brass">
                <span class="w-8 h-8 rounded-[9px] ring-1 ring-brass/80 flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h4l2-6 4 11 2-5h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="font-display text-lg truncate">{{ config('app.name') }}</span>
            </a>
            <div class="flex-1"></div>
            <span class="hidden sm:inline text-sm text-bone/70 tabular-nums" x-data="{ t: '' }"
                  x-init="const tick = () => t = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); tick(); setInterval(tick, 15000)" x-text="t"></span>
            <span class="hidden md:inline text-sm text-bone/70">{{ auth()->user()->name }}</span>
            <a href="{{ route('pos.sales.index') }}" class="text-sm px-3 py-1.5 rounded-lg text-bone/80 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brass">Sales</a>
            @if (auth()->user()->hasAnyRole(['admin', 'manager']))
                <a href="{{ route('admin.dashboard') }}" class="text-sm px-3 py-1.5 rounded-lg text-bone/80 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brass">Admin</a>
            @endif
        </header>
        <div class="flex-1 min-h-0">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
