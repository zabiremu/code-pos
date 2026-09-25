<x-layouts.install :step="5" title="Finish">
    <div class="card p-8 text-center">
        <span class="inline-flex w-12 h-12 rounded-full bg-green-100 text-green-700 items-center justify-center text-2xl mb-4">✓</span>
        <h2 class="text-xl font-semibold mb-3">You're all set</h2>
        <p class="text-sm text-gray-600 mb-2">
            A demo admin account was seeded so you can log in immediately:
        </p>
        <p class="text-sm font-mono bg-gray-100 rounded-lg px-3 py-2 mb-4">
            admin@example.com / password
        </p>
        <p class="alert-error mb-6">Change this password immediately after logging in.</p>
        <a href="{{ route('login') }}" class="inline-block btn-primary">Log in</a>
    </div>
</x-layouts.install>
