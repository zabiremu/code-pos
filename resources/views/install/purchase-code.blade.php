<x-layouts.install :step="3" title="Purchase Code">
    <div class="card p-8">
        <h2 class="text-lg font-semibold mb-2">Verify your purchase</h2>
        <p class="text-sm text-zinc-600 mb-4">
            Enter the purchase code from your CodeCanyon
            <span class="font-medium">Downloads</span> page
            (Download &rarr; &ldquo;License certificate &amp; purchase code&rdquo;).
            It looks like <span class="font-mono text-xs">a1b2c3d4-e5f6-7890-abcd-ef1234567890</span>.
        </p>

        @if ($errors->any())
            <p class="alert-error mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('install.purchase-code.verify') }}" class="space-y-3">
            @csrf
            <div>
                <label class="field-label" for="purchase_code">Purchase code</label>
                <input id="purchase_code" type="text" name="purchase_code" value="{{ old('purchase_code') }}"
                       required autocomplete="off" spellcheck="false" class="w-full input font-mono">
            </div>
            <p class="text-xs text-zinc-500">
                One Regular License covers one live domain
                (<span class="font-mono">{{ request()->getHost() }}</span>).
                Installs on localhost or a local network don&rsquo;t use it up.
            </p>
            <button class="w-full btn-primary">Verify &amp; continue</button>
        </form>
    </div>
</x-layouts.install>
