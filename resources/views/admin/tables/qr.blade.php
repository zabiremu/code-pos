<x-layouts.admin :title="'QR code - '.$table->label">
    <div class="max-w-sm mx-auto card p-6 text-center print:shadow-none print:ring-0">
        <p class="text-xs uppercase tracking-wide text-zinc-400">{{ config('app.name') }}</p>
        <h2 class="text-2xl font-semibold mt-1 mb-4">Table {{ $table->label }}</h2>

        <canvas id="table-qr-canvas" width="220" height="220" class="mx-auto"></canvas>

        <p class="text-sm text-zinc-500 mt-4">Scan to view the menu and order from your phone.</p>

        <div class="mt-4 flex items-center gap-2">
            <input type="text" readonly value="{{ $url }}" class="input flex-1 text-xs" onclick="this.select()">
        </div>

        <div class="flex gap-2 mt-4 print:hidden">
            <button type="button" onclick="window.print()" class="btn-secondary flex-1">Print</button>
            <a href="{{ route('admin.tables.index') }}" class="btn-ghost">Back to tables</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.QRCode.toCanvas(
                document.getElementById('table-qr-canvas'),
                @json($url),
                { width: 220, margin: 1 },
                function (error) {
                    if (error) console.error(error);
                }
            );
        });
    </script>
</x-layouts.admin>
