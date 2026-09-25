<x-layouts.install :step="4" title="Database">
    <div class="card p-8">
        <h2 class="text-lg font-semibold mb-4">Database connection</h2>

        @if ($errors->any())
            <p class="alert-error mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('install.database.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="field-label">Host</label>
                <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" placeholder="127.0.0.1" required class="w-full input">
            </div>
            <div>
                <label class="field-label">Port</label>
                <input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required class="w-full input">
            </div>
            <div>
                <label class="field-label">Database name</label>
                <input type="text" name="db_database" value="{{ old('db_database') }}" required class="w-full input">
            </div>
            <div>
                <label class="field-label">Username</label>
                <input type="text" name="db_username" value="{{ old('db_username') }}" required class="w-full input">
            </div>
            <div>
                <label class="field-label">Password</label>
                <input type="password" name="db_password" class="w-full input">
            </div>
            <button class="w-full btn-primary">Test &amp; install</button>
        </form>
    </div>
</x-layouts.install>
