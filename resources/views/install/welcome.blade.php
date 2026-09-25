<x-layouts.install :step="1" title="Welcome">
    <div class="card p-8 text-center">
        <h2 class="text-xl font-semibold mb-3">Welcome</h2>
        <p class="text-sm text-gray-600 mb-6">
            This wizard checks your server, verifies your Envato purchase code,
            and sets up your database. It takes about two minutes.
        </p>
        <a href="{{ route('install.requirements') }}" class="inline-block btn-primary">
            Get started
        </a>
    </div>
</x-layouts.install>
