{{--
    QR self-ordering menu (no login). A cart is kept client-side in Alpine;
    "Review order" opens a summary sheet, and submitting serializes the
    cart into one hidden "cart" JSON field - see PublicOrderController@store.
--}}
<x-layouts.public :title="'Order &middot; Table '.$table->label">
    <div x-data="{
            cart: {},
            showSummary: false,
            guestName: '',
            qty(id) { return this.cart[id]?.quantity || 0 },
            inc(id, name, price) {
                if (!this.cart[id]) this.cart[id] = { menu_item_id: id, name: name, price: price, quantity: 0 };
                this.cart[id].quantity++;
            },
            dec(id) {
                if (!this.cart[id]) return;
                this.cart[id].quantity--;
                if (this.cart[id].quantity <= 0) delete this.cart[id];
            },
            count() { return Object.values(this.cart).reduce((sum, i) => sum + i.quantity, 0) },
            total() { return Object.values(this.cart).reduce((sum, i) => sum + (i.quantity * i.price), 0) },
            cartPayload() {
                return Object.values(this.cart).map(i => ({ menu_item_id: i.menu_item_id, quantity: i.quantity }));
            },
         }">

        <header class="text-center mb-6">
            <p class="text-xs uppercase tracking-wide text-zinc-400">{{ config('app.name') }}</p>
            <h1 class="text-2xl font-semibold mt-1">Table {{ $table->label }}</h1>
            <p class="text-sm text-zinc-500 mt-1">Tap + to add items, then review your order below.</p>
        </header>

        @if (session('status'))
            <div class="alert-success mb-5">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert-error mb-5">{{ session('cart_error', $errors->first()) }}</div>
        @endif

        <div class="space-y-8">
            @foreach ($categories as $category)
                <section>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 mb-3">{{ $category->name }}</h2>
                    <div class="space-y-3">
                        @foreach ($category->menuItems as $item)
                            <div class="card p-4 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium truncate">{{ $item->name }}</p>
                                    @if ($item->description)
                                        <p class="text-xs text-zinc-500 mt-0.5 line-clamp-2">{{ $item->description }}</p>
                                    @endif
                                    <p class="text-sm font-semibold text-primary-700 mt-1">{{ number_format($item->base_price, 2) }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" x-on:click="dec({{ $item->id }})"
                                            x-show="qty({{ $item->id }}) > 0" x-cloak
                                            class="w-8 h-8 rounded-full bg-zinc-100 text-zinc-600 hover:bg-zinc-200 flex items-center justify-center font-semibold">&minus;</button>
                                    <span x-show="qty({{ $item->id }}) > 0" x-cloak class="w-5 text-center font-medium" x-text="qty({{ $item->id }})"></span>
                                    <button type="button"
                                            x-on:click="inc({{ $item->id }}, {{ \Illuminate\Support\Js::from($item->name) }}, {{ $item->base_price }})"
                                            class="w-8 h-8 rounded-full bg-primary-600 text-white hover:bg-primary-700 flex items-center justify-center font-semibold">+</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            @if ($categories->isEmpty())
                <p class="text-sm text-zinc-400 text-center py-16">The menu isn't available right now - please ask a staff member.</p>
            @endif
        </div>

        {{-- Sticky cart bar --}}
        <div x-show="count() > 0" x-cloak
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             class="fixed bottom-0 inset-x-0 bg-white border-t border-zinc-200 shadow-lg px-4 py-3 z-30">
            <div class="max-w-2xl mx-auto flex items-center justify-between gap-3">
                <div class="text-sm">
                    <span class="font-semibold" x-text="count()"></span> item(s) &middot;
                    <span class="font-semibold" x-text="total().toFixed(2)"></span>
                </div>
                <button type="button" x-on:click="showSummary = true" class="btn-primary">Review order</button>
            </div>
        </div>

        {{-- Review/submit sheet --}}
        <div x-show="showSummary" x-cloak
             class="fixed inset-0 z-40 bg-zinc-900/50 flex items-end sm:items-center justify-center p-0 sm:p-4"
             x-on:click.self="showSummary = false">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-sm max-h-[85vh] overflow-y-auto p-5">
                <h3 class="font-semibold text-lg mb-3">Your order</h3>

                <template x-for="item in Object.values(cart)" :key="item.menu_item_id">
                    <div class="flex items-center justify-between py-2 border-b border-zinc-100 text-sm">
                        <span x-text="item.quantity + '&times; ' + item.name"></span>
                        <span x-text="(item.quantity * item.price).toFixed(2)"></span>
                    </div>
                </template>

                <div class="flex items-center justify-between py-3 font-semibold">
                    <span>Total</span>
                    <span x-text="total().toFixed(2)"></span>
                </div>

                <form method="POST" action="{{ route('order.store', $table) }}"
                      x-on:submit="$refs.cartField.value = JSON.stringify(cartPayload())">
                    @csrf
                    <input type="hidden" name="cart" x-ref="cartField">

                    <label class="field-label">Your name (optional)</label>
                    <input type="text" name="guest_name" x-model="guestName" class="input w-full mb-4" placeholder="e.g. Rahim">

                    <div class="flex gap-2">
                        <button type="button" x-on:click="showSummary = false" class="btn-secondary flex-1">Keep browsing</button>
                        <button type="submit" class="btn-primary flex-1">Place order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.public>
