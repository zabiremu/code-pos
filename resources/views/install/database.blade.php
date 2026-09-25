<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Database &middot; Installer</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow p-8">
        <h1 class="text-lg font-semibold mb-4">Database connection</h1>

        @if ($errors->any())
            <p class="text-sm text-red-600 mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('install.database.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="db_host" placeholder="Host (e.g. 127.0.0.1)" required class="w-full rounded border-gray-300 text-sm">
            <input type="number" name="db_port" value="3306" required class="w-full rounded border-gray-300 text-sm">
            <input type="text" name="db_database" placeholder="Database name" required class="w-full rounded border-gray-300 text-sm">
            <input type="text" name="db_username" placeholder="Username" required class="w-full rounded border-gray-300 text-sm">
            <input type="password" name="db_password" placeholder="Password" class="w-full rounded border-gray-300 text-sm">
            <button class="w-full bg-gray-900 text-white text-sm rounded px-5 py-2">Test &amp; install</button>
        </form>
    </div>
</body>
</html>
