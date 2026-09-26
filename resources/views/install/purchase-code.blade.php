<x-layouts.install :step="3" title="Purchase Code">
    <div class="card p-8">
        <h2 class="text-lg font-semibold mb-2">Verify your purchase</h2>
        <p class="text-sm text-zinc-600 mb-4">
            Find your purchase code under Downloads on CodeCanyon. Your Envato
            Personal Token is generated at
            <span class="font-mono">build.envato.com</span> with the "view your
            purchased items" permission.
        </p>

        @if ($errors->any())
            <p class="alert-error mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('install.purchase-code.verify') }}" class="space-y-3">
            @csrf
            <div>
                <label class="field-label">Purchase code</label>
                <input type="text" name="purchase_code" required class="w-full input">
            </div>
            <div>
                <label class="field-label">Envato Personal Token</label>
                <input type="text" name="envato_token" required class="w-full input">
            </div>
            <button class="w-full btn-primary">Verify &amp; continue</button>
        </form>
    </div>
</x-layouts.install>
