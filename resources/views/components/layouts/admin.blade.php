<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Restaurant POS' }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">

        {{-- Desktop sidebar: plain CSS (hidden md:flex), zero Alpine involvement
             so it renders correctly on first paint with no flash. White, not
             solid red - the brand color shows up in the logo mark, the
             active-nav highlight, and the primary buttons instead. --}}
        <aside class="hidden md:flex md:flex-col w-64 shrink-0 bg-white border-r border-gray-100">
            <x-layouts.sidebar-nav />
        </aside>

        {{-- Mobile drawer: x-cloak + x-show is the safe Alpine pattern here,
             since this one genuinely should start hidden until toggled. --}}
        <div x-show="sidebarOpen" x-cloak
             x-on:click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-gray-900/50 md:hidden"
             x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <aside x-show="sidebarOpen" x-cloak
               x-on:click.outside="sidebarOpen = false"
               class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-white shadow-xl md:hidden"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <x-layouts.sidebar-nav />
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white/80 backdrop-blur border-b border-gray-100 px-4 py-3.5 flex items-center gap-3 sticky top-0 z-20">
                <button type="button" x-on:click="sidebarOpen = true" class="md:hidden text-gray-500 hover:text-primary-600 -ml-1 p-1">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                    </svg>
                </button>
                <h1 class="text-xl font-semibold tracking-tight flex-1 truncate">{{ $title ?? 'Dashboard' }}</h1>
            </header>

            <main class="flex-1 p-4 md:p-6">
                @if (session('status'))
                    <div class="alert-success mb-4">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert-error mb-4">{{ $errors->first() }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
