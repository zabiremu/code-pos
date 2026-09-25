<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Restaurant POS' }} &middot; Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">
        <aside class="w-56 shrink-0 bg-gray-900 text-gray-100 hidden md:flex md:flex-col">
            <div class="px-4 py-4 text-lg font-semibold border-b border-gray-800">Restaurant POS</div>
            <nav class="flex-1 px-2 py-4 space-y-1 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded px-3 py-2 hover:bg-gray-800">Dashboard</a>
                <a href="{{ route('pos.orders.index') }}" class="block rounded px-3 py-2 hover:bg-gray-800">Orders</a>
                {{-- Menu, Tables, Staff, Reports nav items land with their feature-module phases --}}
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="px-3 py-3 border-t border-gray-800">
                @csrf
                <button type="submit" class="text-sm text-gray-300 hover:text-white">Log out</button>
            </form>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="bg-white border-b px-4 py-3 flex items-center justify-between">
                <h1 class="text-lg font-semibold">{{ $title ?? 'Dashboard' }}</h1>
                <span class="text-sm text-gray-500">{{ auth()->user()?->name }}</span>
            </header>

            <main class="flex-1 p-4">
                @if (session('status'))
                    <div class="mb-4 rounded bg-green-50 text-green-800 px-4 py-2 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
