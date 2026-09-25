<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-sm w-full">
        <div class="flex flex-col items-center mb-6">
            <span class="w-12 h-12 rounded-2xl bg-primary-600 flex items-center justify-center shadow-sm mb-3">
                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                    <path d="M4 3v7a3 3 0 0 0 3 3v8M4 3v4M7 3v7M4 7h3M10 3c-1.5 2-1.5 6 0 8v10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 3c-1.7 0-3 2.24-3 5s1.3 5 3 5v8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <h1 class="text-lg font-semibold text-center">{{ config('app.name') }}</h1>
            <p class="text-sm text-gray-500">Sign in to your staff account</p>
        </div>

        <div class="card p-8">
            @if ($errors->any())
                <p class="alert-error mb-4">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="field-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full input">
                </div>
                <div>
                    <label class="field-label">Password</label>
                    <input type="password" name="password" required class="w-full input">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Remember me
                </label>
                <button class="w-full btn-primary">Log in</button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">Accounts are created by an admin — see your manager if you need access.</p>
    </div>
</body>
</html>
