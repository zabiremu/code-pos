<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Restaurant POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg bg-white rounded-lg shadow p-8 text-center">
        <h1 class="text-xl font-semibold mb-3">Restaurant POS Installer</h1>
        <p class="text-sm text-gray-600 mb-6">
            This wizard checks your server, verifies your Envato purchase code,
            and sets up your database. It takes about two minutes.
        </p>
        <a href="{{ route('install.requirements') }}" class="inline-block bg-gray-900 text-white text-sm rounded px-5 py-2">
            Get started
        </a>
    </div>
</body>
</html>
