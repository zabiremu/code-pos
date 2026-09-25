<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Purchase Code &middot; Installer</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow p-8">
        <h1 class="text-lg font-semibold mb-2">Verify your purchase</h1>
        <p class="text-sm text-gray-600 mb-4">
            Find your purchase code under Downloads on CodeCanyon. Your Envato
            Personal Token is generated at
            <span class="font-mono">build.envato.com</span> with the "view your
            purchased items" permission.
        </p>

        @if ($errors->any())
            <p class="text-sm text-red-600 mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('install.purchase-code.verify') }}" class="space-y-3">
            @csrf
            <input type="text" name="purchase_code" placeholder="Purchase code" required class="w-full rounded border-gray-300 text-sm">
            <input type="text" name="envato_token" placeholder="Envato Personal Token" required class="w-full rounded border-gray-300 text-sm">
            <button class="w-full bg-gray-900 text-white text-sm rounded px-5 py-2">Verify &amp; continue</button>
        </form>
    </div>
</body>
</html>
