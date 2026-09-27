@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Collect payment">
    <x-back-link :href="$customer ? route('admin.customers.show', $customer) : route('admin.customer-receipts.index')">{{ $customer ? $customer->name : 'All collections' }}</x-back-link>

    @if (! $customer)
        <form method="GET" action="{{ route('admin.customer-receipts.create') }}" class="card max-w-2xl">
            <div class="px-5">
                <x-form-row label="Customer" for="customer_id" :required="true">
                    <select id="customer_id" name="customer_id" required class="input">
                        <option value="">Choose a customer</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </x-form-row>
            </div>
            <div class="px-5 py-4 border-t border-zinc-100"><button class="btn-primary">Continue</button></div>
        </form>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <form method="POST" action="{{ route('admin.customer-receipts.store') }}" class="card xl:col-span-2" x-data="{ amount: @js(old('amount', $due > 0 ? number_format($due, 2, '.', '') : '')) }">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <div class="card-body !pb-0">
                    <p class="text-sm text-zinc-500">{{ $customer->label() }} owes</p>
                    <p class="font-display text-4xl tabular-nums text-primary-700 mt-1">{{ $money($due) }}</p>
                </div>
                <div class="px-5">
                    <x-form-row label="Amount" for="amount" :required="true" hint="Applied to their oldest bills first.">
                        <div class="flex flex-wrap items-center gap-3">
                            <input id="amount" type="number" step="0.01" min="0.01" max="{{ $due }}" name="amount" x-model="amount" required class="input" style="max-width:12rem">
                            <button type="button" @click="amount = @js(number_format($due, 2, '.', ''))" class="text-sm text-primary-600 font-medium hover:underline underline-offset-4">Full amount</button>
                        </div>
                    </x-form-row>
                    <x-form-row label="Date" for="receipt_date" :required="true">
                        <input id="receipt_date" type="date" name="receipt_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('receipt_date', today()->format('Y-m-d')) }}" class="input" style="max-width:12rem">
                    </x-form-row>
                    <x-form-row label="Method" for="method" :required="true">
                        <select id="method" name="method" class="input" style="max-width:16rem">
                            @foreach (\App\Models\CustomerReceipt::METHODS as $key => $label)
                                <option value="{{ $key }}" @selected(old('method', 'cash') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-form-row label="Reference" for="reference" hint="Transaction ID, cheque no.">
                        <input id="reference" name="reference" maxlength="100" value="{{ old('reference') }}" class="input">
                    </x-form-row>
                    <x-form-row label="Notes" for="notes">
                        <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
                    </x-form-row>
                </div>
                <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
                    <button class="btn-primary" @disabled($due <= 0)>Record collection</button>
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn-secondary">Cancel</a>
                </div>
            </form>

            <section class="card overflow-hidden" aria-labelledby="open-heading">
                <div class="px-5 pt-5 pb-3"><h2 id="open-heading" class="font-display text-xl">Unpaid bills</h2></div>
                <ul class="divide-y divide-zinc-100 border-t border-zinc-100 text-sm">
                    @forelse ($openBills as $b)
                        <li class="px-5 py-2.5 flex justify-between gap-3">
                            <a href="{{ route('pos.register.receipt', $b) }}" class="hover:text-primary-600">#{{ $b->id }} <span class="text-zinc-500">&middot; {{ $b->created_at->format('d M Y') }}</span></a>
                            <span class="tabular-nums">{{ $money($b->balanceDue()) }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-zinc-500">Nothing owed.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif
</x-layouts.admin>
