<x-layouts.install :step="6" title="Finish">
    <div class="card p-8 text-center">
        <span class="inline-flex w-12 h-12 rounded-full bg-green-100 text-green-700 items-center justify-center text-2xl mb-4">✓</span>
        <h2 class="text-xl font-semibold mb-3">You're all set</h2>
        <p class="text-sm text-zinc-600 mb-6">
            {{ config('app.name') }} is installed.
            @if ($adminEmail)
                Log in with <span class="font-medium text-zinc-800">{{ $adminEmail }}</span> and the password you just chose.
            @else
                Log in with the admin account you just created.
            @endif
        </p>
        <a href="{{ route('login') }}" class="inline-block btn-primary">Log in</a>
    </div>
</x-layouts.install>
