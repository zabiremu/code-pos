<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'POS' }} &middot; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gloock&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bone text-zinc-900 antialiased font-sans" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">

        {{-- Desktop sidebar: plain CSS (hidden md:flex), zero Alpine involvement
             so it renders correctly on first paint with no flash. Sticky so
             the blood panel runs the full height while the page scrolls. --}}
        <aside class="hidden md:flex md:flex-col w-64 shrink-0 sidebar-surface sticky top-0 h-screen">
            <x-layouts.sidebar-nav />
        </aside>

        {{-- Mobile drawer: x-cloak + x-show is the safe Alpine pattern here,
             since this one genuinely should start hidden until toggled. --}}
        <div x-show="sidebarOpen" x-cloak
             x-on:click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-clot/60 md:hidden"
             x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <aside x-show="sidebarOpen" x-cloak
               x-on:click.outside="sidebarOpen = false"
               class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col sidebar-surface shadow-xl md:hidden"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <x-layouts.sidebar-nav />
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            @php $activeShift = auth()->user()?->activeShift(); @endphp
            <header class="bg-bone/85 backdrop-blur border-b border-primary-900/10 px-4 md:px-6 py-3.5 flex items-center gap-3 sticky top-0 z-20">
                <button type="button" x-on:click="sidebarOpen = true" class="md:hidden text-zinc-500 hover:text-primary-600 -ml-1 p-1" aria-label="Open menu">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                    </svg>
                </button>
                <h1 class="font-display text-2xl leading-tight flex-1 truncate">{{ $title ?? 'Dashboard' }}</h1>

                {{-- Shift clock-in/out widget - part of Employee Management.
                     Every role sees this, not just admin/manager, since it's
                     each person's own attendance. --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" x-on:click="open = true" class="btn-secondary !px-3 !py-1.5 text-xs gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $activeShift ? 'bg-emerald-500' : 'bg-zinc-300' }} shrink-0"></span>
                        {{ $activeShift ? 'Clocked in '.$activeShift->clock_in->format('g:i A') : 'Clock in' }}
                    </button>

                    <div x-show="open" x-cloak x-on:click.self="open = false"
                         x-on:keydown.escape.window="open = false"
                         class="fixed inset-0 z-40 bg-zinc-900/50 flex items-center justify-center p-4">
                        <div class="card p-5 w-full max-w-xs" x-on:click.outside="open = false">
                            @if ($activeShift)
                                <h3 class="font-semibold mb-1">Clock out</h3>
                                <p class="text-xs text-zinc-500 mb-3">
                                    Clocked in {{ $activeShift->clock_in->diffForHumans() }},
                                    opening till {{ number_format($activeShift->opening_till, 2) }}.
                                </p>
                                <form method="POST" action="{{ route('shifts.clock-out') }}">
                                    @csrf
                                    <label class="field-label">Closing till amount</label>
                                    <input type="number" step="0.01" min="0" name="closing_till" required class="w-full input mb-3" autofocus>
                                    <div class="flex gap-2">
                                        <button type="button" x-on:click="open = false" class="btn-secondary flex-1">Cancel</button>
                                        <button class="btn-primary flex-1">Clock out</button>
                                    </div>
                                </form>
                            @else
                                <h3 class="font-semibold mb-1">Clock in</h3>
                                <p class="text-xs text-zinc-500 mb-3">Enter the cash amount in the till at the start of your shift.</p>
                                <form method="POST" action="{{ route('shifts.clock-in') }}">
                                    @csrf
                                    <label class="field-label">Opening till amount</label>
                                    <input type="number" step="0.01" min="0" value="0" name="opening_till" required class="w-full input mb-3" autofocus>
                                    <div class="flex gap-2">
                                        <button type="button" x-on:click="open = false" class="btn-secondary flex-1">Cancel</button>
                                        <button class="btn-primary flex-1">Clock in</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Profile dropdown: avatar + name, opens a menu for profile/
                     password updates and logout. Closes on an outside click
                     or Escape via Alpine. --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" x-on:click="open = !open"
                            class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-primary-50 transition-colors">
                        <span class="w-8 h-8 rounded-full bg-primary-600 text-white font-semibold text-xs flex items-center justify-center shrink-0">
                            {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}
                        </span>
                        <span class="hidden sm:block text-sm font-medium text-zinc-700 max-w-[10rem] truncate">
                            {{ auth()->user()?->name }}
                        </span>
                        <svg class="w-4 h-4 text-zinc-400 hidden sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak
                         x-on:click.outside="open = false"
                         x-on:keydown.escape.window="open = false"
                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-52 origin-top-right card p-1.5 z-30">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 transition-colors">
                            Update profile
                        </a>
                        <a href="{{ route('profile.edit') }}#password" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 transition-colors">
                            Update password
                        </a>
                        <div class="my-1 border-t border-zinc-100"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-50 hover:text-primary-600 transition-colors">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6 lg:p-8">
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
