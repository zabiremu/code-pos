<x-layouts.install :step="2" title="Requirements">
    <div class="card p-8">
        <h2 class="text-lg font-semibold mb-4">Server requirements</h2>

        <ul class="space-y-1.5 text-sm mb-6">
            <li class="flex items-center gap-2 {{ $phpOk ? 'text-green-700' : 'text-primary-600' }}">
                <span>{{ $phpOk ? '✓' : '✗' }}</span> PHP ≥ 8.2
            </li>
            @foreach ($extensions as $ext => $ok)
                <li class="flex items-center gap-2 {{ $ok ? 'text-green-700' : 'text-primary-600' }}">
                    <span>{{ $ok ? '✓' : '✗' }}</span> {{ $ext }} extension
                </li>
            @endforeach
            @foreach ($writable as $path => $ok)
                <li class="flex items-center gap-2 {{ $ok ? 'text-green-700' : 'text-primary-600' }}">
                    <span>{{ $ok ? '✓' : '✗' }}</span> {{ $path }} writable
                </li>
            @endforeach
        </ul>

        @if ($allOk)
            <a href="{{ route('install.purchase-code') }}" class="inline-block btn-primary">Continue</a>
        @else
            <p class="alert-error">Fix the items above, then refresh this page.</p>
        @endif
    </div>
</x-layouts.install>
