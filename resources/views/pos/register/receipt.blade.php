{{--
    Shown right after a sale. The receipt itself is sized for 80mm thermal
    paper and is the only thing that prints. Enter starts the next sale.
--}}
@php
    $money = fn ($v) => number_format((float) $v, 2);
    $payment = $bill->payments->first();
@endphp
<x-layouts.register title="Receipt">
    <div class="h-full overflow-y-auto" x-data x-init="window.clearRegisterCart?.(); $refs.next.focus()"
         @keydown.window.p.prevent="window.print()">
        <div class="max-w-3xl mx-auto p-4 sm:p-8 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-6 items-start">

            <section class="print:hidden space-y-4 order-2 md:order-1">
                <div class="rounded-2xl sidebar-surface text-bone p-6">
                    <p class="text-sm text-bone/70">Sale complete</p>
                    <p class="font-display text-4xl mt-2 tabular-nums">{{ $currency ? $currency.' ' : '' }}{{ $money($bill->grand_total) }}</p>
                    <p class="text-sm text-bone/70 mt-1">Paid by {{ $methods[$payment?->method] ?? 'cash' }}</p>
                    @if ($change > 0)
                        <div class="mt-5 rounded-xl bg-white/10 px-4 py-3 flex items-baseline justify-between">
                            <span class="text-sm">Change to give</span>
                            <span class="font-display text-3xl tabular-nums text-white">{{ $money($change) }}</span>
                        </div>
                    @endif
                </div>
                <a x-ref="next" href="{{ route('pos.register') }}" class="btn-primary w-full h-14 text-lg rounded-xl">
                    New sale <kbd class="text-[11px] font-normal opacity-70 border border-white/30 rounded px-1.5 py-0.5">Enter</kbd>
                </a>
                <button type="button" onclick="window.print()" class="btn-secondary w-full h-12 rounded-xl">
                    Print receipt <kbd class="text-[11px] font-normal text-zinc-400 border border-zinc-200 rounded px-1.5 py-0.5">P</kbd>
                </button>
                <a href="{{ route('pos.sales.index') }}" class="block text-center text-sm text-zinc-500 hover:text-primary-600">View recent sales</a>
            </section>

            {{-- The printable receipt --}}
            <article id="receipt" class="order-1 md:order-2 bg-white shadow-sm ring-1 ring-zinc-200 rounded-lg mx-auto p-5 font-mono text-[12px] leading-relaxed text-zinc-900"
                     style="width: 80mm; max-width: 100%">
                <header class="text-center">
                    <p class="font-sans font-semibold text-[15px]">{{ config('app.name') }}</p>
                    @if ($shop['address'])<p style="white-space:pre-line">{{ $shop['address'] }}</p>@endif
                    @if ($shop['phone'] || $shop['email'])<p>{{ collect([$shop['phone'], $shop['email']])->filter()->implode(' | ') }}</p>@endif
                </header>
                <div class="border-t border-dashed border-zinc-400 my-2"></div>
                <div class="flex justify-between"><span>Receipt #{{ $bill->id }}</span><span>{{ $bill->created_at->format('d/m/Y H:i') }}</span></div>
                <div>Cashier: {{ $bill->sale->cashier?->name }}</div>
                <div class="border-t border-dashed border-zinc-400 my-2"></div>

                @foreach ($bill->sale->items as $item)
                    <div class="flex justify-between gap-2"><span class="min-w-0">{{ $item->product?->name }}</span><span class="shrink-0">{{ $money($item->lineTotal()) }}</span></div>
                    <div class="text-zinc-500 pl-2">{{ $item->quantity }}{{ $item->product?->unit ? ' '.$item->product->unit->short_name : '' }} &times; {{ $money($item->unit_price) }}</div>
                @endforeach

                <div class="border-t border-dashed border-zinc-400 my-2"></div>
                <div class="flex justify-between"><span>Subtotal</span><span>{{ $money($bill->subtotal) }}</span></div>
                @if ($bill->tax_total > 0)<div class="flex justify-between"><span>Tax</span><span>{{ $money($bill->tax_total) }}</span></div>@endif
                @if ($bill->discount_total > 0)<div class="flex justify-between"><span>Discount</span><span>-{{ $money($bill->discount_total) }}</span></div>@endif
                <div class="flex justify-between font-bold text-[14px] mt-1"><span>TOTAL{{ $currency ? ' '.$currency : '' }}</span><span>{{ $money($bill->grand_total) }}</span></div>
                <div class="border-t border-dashed border-zinc-400 my-2"></div>
                <div class="flex justify-between"><span>{{ $methods[$payment?->method] ?? 'Cash' }}</span><span>{{ $money($bill->grand_total + $change) }}</span></div>
                @if ($change > 0)<div class="flex justify-between"><span>Change</span><span>{{ $money($change) }}</span></div>@endif
                @if ($payment?->reference)<div>Ref: {{ $payment->reference }}</div>@endif

                @if ($shop['footer'])
                    <div class="border-t border-dashed border-zinc-400 my-2"></div>
                    <p class="text-center" style="white-space:pre-line">{{ $shop['footer'] }}</p>
                @endif
            </article>
        </div>
    </div>

    <style>
        @media print {
            @page { size: 80mm auto; margin: 0; }
            html, body { background: #fff !important; height: auto !important; }
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; left: 0; top: 0; box-shadow: none; border: 0; border-radius: 0; margin: 0; padding: 4mm; }
        }
    </style>
</x-layouts.register>
