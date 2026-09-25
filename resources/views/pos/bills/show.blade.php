<x-layouts.admin :title="'Bill #'.$bill->id">
    <div class="max-w-md mx-auto card p-6 font-mono text-sm">
        <div class="text-center mb-4">
            <p class="font-semibold text-base">{{ config('app.name') }}</p>
            <p class="text-xs text-gray-500">Order #{{ $bill->order_id }} &middot; {{ $bill->order->table?->label ?? ucfirst($bill->order->type) }}</p>
            <p class="text-xs text-gray-500">{{ $bill->created_at->format('Y-m-d H:i') }}</p>
        </div>

        <div class="border-t border-dashed my-3"></div>

        @foreach ($bill->order->items as $item)
            <div class="flex justify-between">
                <span>{{ $item->quantity }}× {{ $item->menuItem->name }}</span>
                <span>{{ number_format($item->lineTotal(), 2) }}</span>
            </div>
        @endforeach

        <div class="border-t border-dashed my-3"></div>

        <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($bill->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span>Tax</span><span>{{ number_format($bill->tax_total, 2) }}</span></div>
        @if ($bill->service_charge > 0)
            <div class="flex justify-between"><span>Service charge</span><span>{{ number_format($bill->service_charge, 2) }}</span></div>
        @endif
        @if ($bill->discount_total > 0)
            <div class="flex justify-between text-green-700">
                <span>Discount{{ $bill->discount ? ' ('.$bill->discount->name.')' : '' }}</span>
                <span>-{{ number_format($bill->discount_total, 2) }}</span>
            </div>
        @endif
        <div class="flex justify-between font-semibold text-base border-t mt-2 pt-2">
            <span>Total</span><span>{{ number_format($bill->grand_total, 2) }}</span>
        </div>
        <div class="flex justify-between text-gray-500">
            <span>Paid</span><span>{{ number_format($bill->amountPaid(), 2) }}</span>
        </div>
        <div class="flex justify-between font-semibold {{ $bill->balanceDue() > 0 ? 'text-primary-600' : 'text-green-700' }}">
            <span>Balance</span><span>{{ number_format($bill->balanceDue(), 2) }}</span>
        </div>

        @if ($bill->balanceDue() > 0)
            <form method="POST" action="{{ route('pos.bills.payments.store', $bill) }}" class="mt-5 space-y-2 font-sans">
                @csrf
                <select name="method" class="w-full input">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="mobile_wallet">Mobile wallet</option>
                    <option value="other">Other</option>
                </select>
                <input type="number" step="0.01" name="amount" value="{{ $bill->balanceDue() }}" class="w-full input">
                <input type="text" name="reference" placeholder="Reference (optional)" class="w-full input">
                <button class="w-full btn-success">Record payment</button>
            </form>
        @else
            <p class="mt-5 text-center text-green-700 font-sans">Paid in full.</p>
        @endif
    </div>
</x-layouts.admin>
