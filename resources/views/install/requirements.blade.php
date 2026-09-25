<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Requirements &middot; Installer</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow p-8">
        <h1 class="text-lg font-semibold mb-4">Server requirements</h1>

        <ul class="space-y-1 text-sm mb-6">
            <li class="{{ $phpOk ? 'text-green-700' : 'text-red-600' }}">PHP ≥ 8.2 {{ $phpOk ? '✓' : '✗' }}</li>
            @foreach ($extensions as $ext => $ok)
                <li class="{{ $ok ? 'text-green-700' : 'text-red-600' }}">{{ $ext }} extension {{ $ok ? '✓' : '✗' }}</li>
            @endforeach
            @foreach ($writable as $path => $ok)
                <li class="{{ $ok ? 'text-green-700' : 'text-red-600' }}">{{ $path }} writable {{ $ok ? '✓' : '✗' }}</li>
            @endforeach
        </ul>

        @if ($allOk)
            <a href="{{ route('install.purchase-code') }}" class="inline-block bg-gray-900 text-white text-sm rounded px-5 py-2">Continue</a>
        @else
            <p class="text-sm text-red-600">Fix the items above, then refresh this page.</p>
        @endif
    </div>
</body>
</html>
