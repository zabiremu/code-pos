<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Done &middot; Installer</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg bg-white rounded-lg shadow p-8 text-center">
        <h1 class="text-xl font-semibold mb-3">You're all set</h1>
        <p class="text-sm text-gray-600 mb-2">
            A demo admin account was seeded so you can log in immediately:
        </p>
        <p class="text-sm font-mono bg-gray-100 rounded px-3 py-2 mb-6">
            admin@example.com / password
        </p>
        <p class="text-xs text-red-600 mb-6">Change this password immediately after logging in.</p>
        <a href="{{ route('login') }}" class="inline-block bg-gray-900 text-white text-sm rounded px-5 py-2">Log in</a>
    </div>
</body>
</html>
