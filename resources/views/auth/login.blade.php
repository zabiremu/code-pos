<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-sm w-full bg-white rounded-lg shadow p-8">
        <h1 class="text-lg font-semibold mb-6 text-center">{{ config('app.name') }}</h1>

        @if ($errors->any())
            <p class="text-sm text-red-600 mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-sm mb-1">Password</label>
                <input type="password" name="password" required class="w-full rounded border-gray-300 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <button class="w-full bg-gray-900 text-white text-sm rounded px-4 py-2">Log in</button>
        </form>
    </div>
</body>
</html>
