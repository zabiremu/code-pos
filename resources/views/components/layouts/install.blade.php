@props(['step' => 1, 'title' => null])
@php
    $steps = ['Welcome', 'Requirements', 'Purchase Code', 'Database', 'Finish'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' &middot; ' : '' }}Installer &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full">
        <div class="flex flex-col items-center mb-6">
            <span class="w-12 h-12 rounded-2xl bg-primary-600 flex items-center justify-center shadow-sm mb-3">
                <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                    <path d="M4 3v7a3 3 0 0 0 3 3v8M4 3v4M7 3v7M4 7h3M10 3c-1.5 2-1.5 6 0 8v10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 3c-1.7 0-3 2.24-3 5s1.3 5 3 5v8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <h1 class="text-lg font-semibold text-center">{{ config('app.name') }} Installer</h1>

            <div class="flex items-center gap-1.5 mt-4">
                @foreach ($steps as $i => $label)
                    <span class="h-1.5 rounded-full transition-colors {{ $i + 1 <= $step ? 'bg-primary-600 w-8' : 'bg-zinc-200 w-5' }}"
                          title="{{ $label }}"></span>
                @endforeach
            </div>
            <p class="text-xs text-zinc-400 mt-2">Step {{ $step }} of {{ count($steps) }} &middot; {{ $steps[$step - 1] ?? '' }}</p>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
