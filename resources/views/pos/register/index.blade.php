{{--
    The register. Left: find products (search / scan / category / tap).
    Right (bottom sheet on phones): the cart, totals and Pay.
    Logic: resources/js/register.js. Keys: F2 or / search, F9 pay, Esc back.
--}}
@php $cur = $currency ? $currency.' ' : ''; @endphp
<x-layouts.register title="Register">
    <div class="h-full flex" x-data="register({ products: @js($products), defaultTax: @js($defaultTax), oldTendered: @js(old('tendered')), customers: @js($customers), storeCustomerUrl: @js(route('pos.register.customers.store')) })"
         @keydown.window="keys($event)">

        {{-- ============================== Products ============================== --}}
        <section class="flex-1 min-w-0 flex flex-col" aria-label="Products">
            <div class="p-3 sm:p-4 pb-2 space-y-3 shrink-0">
                @if ($errors->any())
                    <div class="rounded-xl bg-primary-50 ring-1 ring-primary-600/20 px-4 py-3 text-sm text-primary-900" role="alert">
                        {{ $errors->first() }} Your cart is still here.
                    </div>
                @endif

                <form @submit.prevent="submitSearch()" class="relative" role="search">
                    <svg class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
                    <input x-ref="search" x-model="search" type="search" inputmode="search" autocomplete="off" aria-label="Search products or scan a barcode"
                           placeholder="Search or scan barcode"
                           class="w-full h-12 rounded-xl border border-zinc-300 bg-white pl-11 pr-14 text-base shadow-sm placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <kbd class="hidden sm:block absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-zinc-400 border border-zinc-200 rounded px-1.5 py-0.5">F2</kbd>
                </form>

                @if ($categories->isNotEmpty())
                    <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1" role="group" aria-label="Filter by category">
                        <button type="button" @click="category = null" :aria-pressed="category === null"
                                :class="category === null ? 'bg-primary-600 text-white' : 'bg-white text-zinc-700 ring-1 ring-inset ring-zinc-200 hover:ring-zinc-300'"
                                class="shrink-0 px-3.5 py-1.5 rounded-full text-sm font-medium transition-colors">All</button>
                        @foreach ($categories as $c)
                            <button type="button" @click="category = category === {{ $c->id }} ? null : {{ $c->id }}" :aria-pressed="category === {{ $c->id }}"
                                    :class="category === {{ $c->id }} ? 'bg-primary-600 text-white' : 'bg-white text-zinc-700 ring-1 ring-inset ring-zinc-200 hover:ring-zinc-300'"
                                    class="shrink-0 px-3.5 py-1.5 rounded-full text-sm font-medium transition-colors">{{ $c->name }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex-1 overflow-y-auto px-3 sm:px-4 pb-28 lg:pb-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5 sm:gap-3">
                    <template x-for="p in filtered" :key="p.id">
                        <button type="button" @click="add(p)"
                                class="group text-left bg-white rounded-xl ring-1 ring-zinc-200/80 shadow-sm hover:ring-primary-300 hover:shadow active:scale-[.98] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 flex flex-col overflow-hidden"
                                :class="p.track && left(p) <= 0 ? 'opacity-60' : ''">
                            <span class="block aspect-[4/3] bg-zinc-100 overflow-hidden" x-show="hasImages" aria-hidden="true">
                                <template x-if="p.image"><img :src="p.image" alt="" loading="lazy" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform"></template>
                                <template x-if="!p.image"><span class="w-full h-full flex items-center justify-center font-display text-3xl text-zinc-300" x-text="p.name.charAt(0).toUpperCase()"></span></template>
                            </span>
                            <span class="p-3 sm:p-3.5 flex flex-col flex-1 min-h-[6.5rem]">
                            <span class="text-sm font-medium leading-snug text-zinc-900 line-clamp-2" x-text="p.name"></span>
                            <span class="text-xs text-zinc-400 mt-0.5 truncate" x-show="p.sku" x-text="p.sku"></span>
                            <span class="mt-auto pt-2 flex items-end justify-between gap-2">
                                <span>
                                    <span class="block text-xs text-zinc-400 line-through tabular-nums" x-show="p.regular" x-text="money(p.regular)"></span>
                                    <span class="block font-semibold tabular-nums text-primary-700" x-text="money(p.price)"></span>
                                </span>
                                <span class="text-[11px] tabular-nums px-1.5 py-0.5 rounded"
                                      x-show="p.track"
                                      :class="left(p) <= 0 ? 'bg-primary-50 text-primary-700' : 'bg-zinc-100 text-zinc-500'"
                                      x-text="left(p) <= 0 ? 'Out' : left(p) + (p.unit ? ' ' + p.unit : '')"></span>
                            </span>
                            </span>
                        </button>
                    </template>
                </div>
                <p x-show="filtered.length === 0" x-cloak class="text-center text-zinc-500 py-16">
                    <template x-if="search">
                        <span>Nothing matches "<span x-text="search"></span>". <button type="button" class="text-primary-600 underline underline-offset-4" @click="search = ''">Clear search</button></span>
                    </template>
                    <template x-if="!search"><span>No products here yet.</span></template>
                </p>
            </div>
        </section>

        {{-- ============================== Cart ============================== --}}
        {{-- Desktop: fixed right column. Phone/tablet: slide-up sheet. --}}
        <div x-show="cartOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-clot/50 lg:hidden" @click="cartOpen = false"></div>
        <aside aria-label="Current sale"
               class="bg-white border-l border-zinc-200 flex flex-col
                      fixed inset-x-0 bottom-0 z-40 max-h-[88vh] rounded-t-2xl shadow-2xl transition-transform duration-200
                      lg:static lg:z-auto lg:max-h-none lg:rounded-none lg:shadow-none lg:w-[380px] xl:w-[420px] lg:translate-y-0"
               :class="cartOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'">
            <div class="px-4 pt-4 pb-3 flex items-center justify-between gap-3 border-b border-zinc-100">
                <h2 class="font-display text-xl">Current sale <span class="text-sm font-sans text-zinc-400" x-show="itemCount" x-text="'(' + itemCount + ')'"></span></h2>
                <div class="flex items-center gap-1">
                    <button type="button" @click="clearCart()" x-show="cart.length" class="text-sm text-zinc-500 hover:text-primary-600 px-2 py-1 rounded">Clear</button>
                    <button type="button" @click="cartOpen = false" class="lg:hidden p-2 rounded-lg text-zinc-400 hover:bg-zinc-100" aria-label="Close cart">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <button type="button" @click="openCustomer()" class="mx-4 mt-3 flex items-center gap-3 rounded-xl px-3 py-2.5 ring-1 ring-inset text-left transition-colors hover:bg-zinc-50"
                    :class="customer ? 'ring-primary-200 bg-primary-50/50' : 'ring-zinc-200'">
                <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-semibold"
                      :class="customer ? 'bg-primary-600 text-white' : 'bg-zinc-100 text-zinc-400'">
                    <span x-show="customer" x-text="customer?.name?.charAt(0)?.toUpperCase()"></span>
                    <svg x-show="!customer" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6" stroke-linecap="round"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium truncate" x-text="customer ? customer.name : 'Walk-in customer'"></span>
                    <span class="block text-xs text-zinc-500 truncate" x-text="customer ? (customer.phone || 'No phone') : 'Add a customer to sell on credit'"></span>
                </span>
                <kbd class="hidden sm:block text-[11px] text-zinc-400 border border-zinc-200 rounded px-1.5 py-0.5">F4</kbd>
            </button>

            <ul class="flex-1 overflow-y-auto divide-y divide-zinc-100 min-h-[8rem]">
                <template x-for="line in cart" :key="line.id">
                    <li class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium leading-snug" x-text="byId(line.id)?.name"></p>
                                <p class="text-xs text-zinc-500 tabular-nums" x-text="money(byId(line.id)?.price) + ' each'"></p>
                                <p class="text-xs text-primary-600 mt-0.5" x-show="stockWarning(line)" x-text="stockWarning(line)"></p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums shrink-0" x-text="money(lineTotal(line))"></p>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="inline-flex items-center rounded-lg ring-1 ring-zinc-200 overflow-hidden">
                                <button type="button" @click="dec(line)" class="w-9 h-9 flex items-center justify-center text-zinc-600 hover:bg-zinc-100" :aria-label="'One less ' + byId(line.id)?.name">&minus;</button>
                                <input type="number" min="1" inputmode="numeric" :value="line.qty" @change="setQty(line, $event.target.value)" @focus="$event.target.select()"
                                       class="w-12 h-9 text-center text-sm tabular-nums border-x border-zinc-200 focus:outline-none focus:bg-primary-50" :aria-label="'Quantity of ' + byId(line.id)?.name">
                                <button type="button" @click="inc(line)" class="w-9 h-9 flex items-center justify-center text-zinc-600 hover:bg-zinc-100" :aria-label="'One more ' + byId(line.id)?.name">+</button>
                            </div>
                            <button type="button" @click="removeLine(line)" class="text-xs text-zinc-400 hover:text-primary-600 px-2 py-1">Remove</button>
                        </div>
                    </li>
                </template>
                <li x-show="!cart.length" class="px-6 py-12 text-center text-sm text-zinc-500">
                    Tap a product, or scan a barcode, to start the sale.
                </li>
            </ul>

            <div class="border-t border-zinc-200 px-4 py-3 space-y-1.5 text-sm bg-zinc-50/60">
                <div class="flex justify-between"><span class="text-zinc-500">Subtotal</span><span class="tabular-nums" x-text="money(subtotal)"></span></div>
                <div class="flex justify-between"><span class="text-zinc-500">Tax</span><span class="tabular-nums" x-text="money(tax)"></span></div>
                <div class="flex items-center justify-between gap-2">
                    <button type="button" @click="showDiscount = !showDiscount; $nextTick(() => $refs.discount?.focus())" class="text-zinc-500 hover:text-primary-600 underline decoration-dotted underline-offset-4">Discount</button>
                    <span class="tabular-nums" :class="discount > 0 ? 'text-emerald-700' : 'text-zinc-400'" x-text="discount > 0 ? '−' + money(discount) : 'None'"></span>
                </div>
                <div x-show="showDiscount" x-cloak class="flex items-center gap-2 pt-1">
                    <input x-ref="discount" x-model="discountInput" type="number" min="0" step="0.01" placeholder="0" aria-label="Discount" class="input h-9 text-right">
                    <div class="inline-flex rounded-lg ring-1 ring-zinc-300 overflow-hidden shrink-0 text-sm" role="group" aria-label="Discount type">
                        <button type="button" @click="discountMode = 'amount'" :aria-pressed="discountMode === 'amount'" :class="discountMode === 'amount' ? 'bg-primary-600 text-white' : 'bg-white text-zinc-600'" class="px-3 h-9">{{ $currency ?: 'Amt' }}</button>
                        <button type="button" @click="discountMode = 'percent'" :aria-pressed="discountMode === 'percent'" :class="discountMode === 'percent' ? 'bg-primary-600 text-white' : 'bg-white text-zinc-600'" class="px-3 h-9">%</button>
                    </div>
                </div>
            </div>

            <div class="px-4 pt-3 pb-4 border-t border-zinc-200">
                <div class="flex items-baseline justify-between mb-3">
                    <span class="text-sm text-zinc-500">Total{{ $currency ? ' ('.$currency.')' : '' }}</span>
                    <span class="font-display text-4xl tabular-nums" x-text="money(total)"></span>
                </div>
                <button type="button" @click="openPay()" :disabled="!cart.length"
                        class="btn-primary w-full h-14 text-lg rounded-xl">
                    Pay <span class="tabular-nums" x-text="money(total)"></span>
                    <kbd class="hidden sm:inline text-[11px] font-normal opacity-70 border border-white/30 rounded px-1.5 py-0.5 ml-1">F9</kbd>
                </button>
            </div>
        </aside>

        {{-- Phone/tablet: always-visible bottom bar that opens the cart sheet. --}}
        <div class="lg:hidden fixed inset-x-0 bottom-0 z-20 p-3 bg-gradient-to-t from-bone via-bone to-transparent" x-show="!cartOpen">
            <div class="flex gap-2">
                <button type="button" @click="cartOpen = true" class="flex-1 h-14 rounded-xl bg-white ring-1 ring-zinc-200 shadow-sm px-4 flex items-center justify-between">
                    <span class="text-sm text-zinc-600"><span class="font-semibold text-zinc-900" x-text="itemCount"></span> <span x-text="itemCount === 1 ? 'item' : 'items'"></span></span>
                    <span class="font-semibold tabular-nums" x-text="money(total)"></span>
                </button>
                <button type="button" @click="openPay()" :disabled="!cart.length" class="btn-primary h-14 px-6 rounded-xl text-base">Pay</button>
            </div>
        </div>

        {{-- ============================== Pay ============================== --}}
        <div x-show="payOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="pay-title">
            <div class="absolute inset-0 bg-clot/60" @click="payOpen = false" x-show="payOpen" x-transition.opacity></div>
            <form method="POST" action="{{ route('pos.register.checkout') }}" @submit="complete($event)"
                  class="relative w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
                  x-show="payOpen" x-transition>
                @csrf
                <template x-for="(line, i) in cart" :key="line.id">
                    <span>
                        <input type="hidden" :name="'items[' + i + '][product_id]'" :value="line.id">
                        <input type="hidden" :name="'items[' + i + '][quantity]'" :value="line.qty">
                    </span>
                </template>
                <input type="hidden" name="discount" :value="discount.toFixed(2)">
                <input type="hidden" name="method" :value="method">
                <input type="hidden" name="customer_id" :value="customer?.id ?? ''">
                <input type="hidden" name="paid_now" :value="method === 'due' ? (Number(paidNow) || 0) : ''">

                <div class="sidebar-surface text-bone px-6 pt-5 pb-6">
                    <div class="flex items-center justify-between">
                        <h2 id="pay-title" class="text-sm text-bone/70">Amount due{{ $currency ? ' ('.$currency.')' : '' }}</h2>
                        <button type="button" @click="payOpen = false" class="p-1.5 -mr-1.5 rounded-lg text-bone/70 hover:bg-white/10" aria-label="Back to the sale">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                    <p class="font-display text-5xl mt-2 tabular-nums" x-text="money(total)"></p>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-2" :class="customer ? 'sm:grid-cols-5' : 'sm:grid-cols-4'" role="radiogroup" aria-label="Payment method">
                        @foreach ($methods as $key => $label)
                            <button type="button" role="radio" @click="pickMethod('{{ $key }}')" :aria-checked="method === '{{ $key }}'"
                                    :class="method === '{{ $key }}' ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-zinc-700 ring-zinc-300 hover:ring-zinc-400'"
                                    class="h-11 rounded-lg ring-1 ring-inset text-sm font-medium transition-colors">{{ $label }}</button>
                        @endforeach
                        <button type="button" role="radio" x-show="customer" x-cloak @click="pickMethod('due')" :aria-checked="method === 'due'"
                                :class="method === 'due' ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-zinc-700 ring-zinc-300 hover:ring-zinc-400'"
                                class="h-11 rounded-lg ring-1 ring-inset text-sm font-medium transition-colors col-span-2 sm:col-span-1">Pay later</button>
                    </div>

                    <div x-show="method === 'due'" x-cloak class="space-y-3">
                        <label for="paid_now" class="block text-sm font-medium text-zinc-700">Paid now in cash <span class="font-normal text-zinc-400">(optional)</span></label>
                        <input id="paid_now" x-ref="paidNow" x-model="paidNow" type="number" step="0.01" min="0" :max="total" inputmode="decimal" placeholder="0.00" class="input h-12 text-xl text-right tabular-nums">
                        <div class="flex items-baseline justify-between rounded-xl px-4 py-3 bg-primary-50">
                            <span class="text-sm text-primary-800">Goes on <span class="font-semibold" x-text="customer?.name"></span>'s account</span>
                            <span class="font-display text-3xl tabular-nums text-primary-700" x-text="money(owed)"></span>
                        </div>
                    </div>

                    <div x-show="method === 'cash'" class="space-y-3">
                        <label for="tendered" class="block text-sm font-medium text-zinc-700">Cash received</label>
                        <input id="tendered" x-ref="tendered" x-model="tendered" name="tendered" :disabled="method !== 'cash'" type="number" step="0.01" min="0" inputmode="decimal"
                               :placeholder="money(total)" class="input h-12 text-xl text-right tabular-nums">
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="amt in quickAmounts()" :key="amt">
                                <button type="button" @click="tendered = amt.toFixed(2)" class="h-10 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-sm tabular-nums" x-text="amt === total ? 'Exact' : money(amt)"></button>
                            </template>
                        </div>
                        <div class="flex items-baseline justify-between rounded-xl px-4 py-3" :class="short ? 'bg-primary-50' : 'bg-emerald-50'">
                            <span class="text-sm" :class="short ? 'text-primary-800' : 'text-emerald-800'" x-text="short ? 'Still short' : 'Change to give'"></span>
                            <span class="font-display text-3xl tabular-nums" :class="short ? 'text-primary-700' : 'text-emerald-800'"
                                  x-text="short ? money(total - (Number(tendered) || 0)) : money(tendered === '' ? 0 : change)"></span>
                        </div>
                    </div>

                    <div x-show="method !== 'cash' && method !== 'due'" x-cloak>
                        <label for="reference" class="block text-sm font-medium text-zinc-700 mb-1.5">Reference <span class="font-normal text-zinc-400">(optional)</span></label>
                        <input id="reference" x-ref="reference" x-model="reference" name="reference" maxlength="100" class="input h-11"
                               :placeholder="method === 'mobile_wallet' ? 'Transaction ID' : (method === 'card' ? 'Last 4 digits or approval code' : '')">
                    </div>

                    @error('tendered')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror
                    @error('paid_now')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror
                    @error('customer_id')<p class="text-sm text-primary-600">{{ $message }}</p>@enderror

                    <button type="submit" :disabled="(method !== 'due' && short) || submitting" class="btn-primary w-full h-14 text-lg rounded-xl">
                        <span x-show="!submitting">Complete sale</span>
                        <span x-show="submitting" x-cloak>Saving&hellip;</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================== Customer ============================== --}}
        <div x-show="customerOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="cust-title">
            <div class="absolute inset-0 bg-clot/60" @click="customerOpen = false"></div>
            <div class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[85vh]" x-show="customerOpen" x-transition>
                <div class="px-5 pt-5 pb-3 flex items-center justify-between">
                    <h2 id="cust-title" class="font-display text-xl">Customer</h2>
                    <button type="button" @click="customerOpen = false" class="p-1.5 rounded-lg text-zinc-400 hover:bg-zinc-100" aria-label="Close">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="px-5">
                    <input x-ref="customerSearch" x-model="customerSearch" type="search" placeholder="Search name or phone" aria-label="Search customers" class="input h-11"
                           @keydown.enter.prevent="customerMatches.length === 1 && chooseCustomer(customerMatches[0])">
                </div>
                <ul class="flex-1 overflow-y-auto mt-3 border-t border-zinc-100 divide-y divide-zinc-100 min-h-[6rem]">
                    <li>
                        <button type="button" @click="chooseCustomer(null)" class="w-full text-left px-5 py-3 hover:bg-zinc-50 text-sm" :class="!customer ? 'font-semibold text-primary-700' : 'text-zinc-600'">Walk-in customer (no account)</button>
                    </li>
                    <template x-for="c in customerMatches" :key="c.id">
                        <li>
                            <button type="button" @click="chooseCustomer(c)" class="w-full text-left px-5 py-3 hover:bg-zinc-50 flex justify-between gap-3" :class="customer?.id === c.id ? 'bg-primary-50/60' : ''">
                                <span class="text-sm font-medium truncate" x-text="c.name"></span>
                                <span class="text-sm text-zinc-500 tabular-nums shrink-0" x-text="c.phone ?? ''"></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="customerSearch && customerMatches.length === 0" class="px-5 py-4 text-sm text-zinc-500">No customer matches. Add them below.</li>
                </ul>
                <form @submit.prevent="createCustomer()" class="border-t border-zinc-200 bg-zinc-50/70 px-5 py-4 space-y-2 rounded-b-2xl">
                    <p class="text-sm font-medium">New customer</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <input x-model="newCustomer.name" required maxlength="150" placeholder="Name" aria-label="New customer name" class="input h-10">
                        <input x-model="newCustomer.phone" type="tel" maxlength="30" placeholder="Phone" aria-label="New customer phone" class="input h-10">
                    </div>
                    <p x-show="customerError" x-text="customerError" class="text-sm text-primary-600"></p>
                    <button class="btn-primary w-full">Add and select</button>
                </form>
            </div>
        </div>

        {{-- Tiny confirmation toast --}}
        <div x-show="flash" x-cloak x-transition.opacity class="fixed left-1/2 -translate-x-1/2 bottom-24 lg:bottom-6 z-50 bg-zinc-900/90 text-white text-sm px-4 py-2 rounded-full shadow-lg pointer-events-none" role="status" x-text="flash"></div>
    </div>
</x-layouts.register>
