/**
 * The register (resources/views/pos/register/index.blade.php).
 *
 * The cart lives here and in localStorage (so a refresh or a failed
 * checkout doesn't lose it). "Complete sale" posts the cart once; the
 * server re-prices everything, so these totals are only a preview.
 */
const STORAGE_KEY = 'shoppulse_register_cart';

export default function register({ products = [], defaultTax = 0, oldTendered = null, customers = [], storeCustomerUrl = '' } = {}) {
    return {
        products,
        defaultTax,
        search: '',
        category: null,
        cart: [],
        discountMode: 'amount', // amount | percent
        discountInput: '',
        showDiscount: false,
        payOpen: false,
        cartOpen: false, // mobile sheet
        method: 'cash',
        tendered: oldTendered ?? '',
        reference: '',
        flash: '',
        submitting: false,
        customers,
        customer: null, // { id, name, phone }
        customerOpen: false,
        customerSearch: '',
        newCustomer: { name: '', phone: '' },
        customerError: '',
        paidNow: '',

        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
                if (saved?.cart) {
                    // Drop anything no longer sellable.
                    this.cart = saved.cart.filter((l) => this.byId(l.id));
                    this.discountMode = saved.discountMode ?? 'amount';
                    this.discountInput = saved.discountInput ?? '';
                    this.showDiscount = this.discountInput !== '';
                    this.customer = saved.customer ? this.customers.find((c) => c.id === saved.customer.id) ?? null : null;
                }
            } catch (e) { /* storage unavailable - start empty */ }

            this.$watch('cart', () => this.persist(), { deep: true });
            this.$watch('discountInput', () => this.persist());
            this.$watch('discountMode', () => this.persist());
            this.$watch('customer', () => this.persist());
            this.$nextTick(() => this.$refs.search?.focus());
        },

        persist() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify({ cart: this.cart, discountMode: this.discountMode, discountInput: this.discountInput, customer: this.customer }));
            } catch (e) { /* ignore */ }
        },

        byId(id) {
            return this.products.find((p) => p.id === id);
        },

        /** Show the photo area only once at least one product has a photo, so a shop without photos keeps compact cards. */
        get hasImages() {
            return this.products.some((p) => p.image);
        },

        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.products.filter((p) =>
                (this.category === null || p.category_id === this.category) &&
                (q === '' || p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q)));
        },

        /* ---------------------------------------------------------------- cart */

        add(product, qty = 1) {
            const line = this.cart.find((l) => l.id === product.id);
            if (line) {
                line.qty += qty;
            } else {
                this.cart.push({ id: product.id, qty });
            }
            this.toast(`${product.name} added`);
        },

        inc(line) { line.qty++; },
        dec(line) { line.qty > 1 ? line.qty-- : this.removeLine(line); },
        setQty(line, value) {
            const n = Math.floor(Number(value));
            line.qty = Number.isFinite(n) && n > 0 ? Math.min(n, 9999) : 1;
        },
        removeLine(line) { this.cart = this.cart.filter((l) => l !== line); },

        clearCart() {
            if (this.cart.length && !confirm('Clear the whole sale?')) return;
            this.cart = [];
            this.discountInput = '';
            this.showDiscount = false;
            this.customer = null;
            this.$refs.search?.focus();
        },

        /** Enter in the search box: an exact SKU/barcode match or a single result goes straight into the cart. */
        submitSearch() {
            const q = this.search.trim().toLowerCase();
            if (!q) return;
            const exact = this.products.find((p) => (p.sku ?? '').toLowerCase() === q);
            const hit = exact ?? (this.filtered.length === 1 ? this.filtered[0] : null);
            if (hit) {
                this.add(hit);
                this.search = '';
            } else {
                this.toast(this.filtered.length ? 'Several matches - tap one' : `Nothing matches "${this.search}"`);
            }
        },

        /** Stock left after what's already in this cart. */
        left(p) {
            return p.stock - (this.cart.find((l) => l.id === p.id)?.qty ?? 0);
        },

        stockWarning(line) {
            const p = this.byId(line.id);
            return p?.track && line.qty > p.stock ? `Only ${Math.max(p.stock, 0)} in stock` : null;
        },

        /* -------------------------------------------------------------- totals */

        lineTotal(line) { return (this.byId(line.id)?.price ?? 0) * line.qty; },
        get itemCount() { return this.cart.reduce((n, l) => n + l.qty, 0); },
        get subtotal() { return this.cart.reduce((s, l) => s + this.lineTotal(l), 0); },
        get tax() {
            return this.cart.reduce((s, l) => {
                const p = this.byId(l.id);
                const rate = p?.tax ?? this.defaultTax;
                return s + this.lineTotal(l) * rate / 100;
            }, 0);
        },
        get discount() {
            const v = Math.max(Number(this.discountInput) || 0, 0);
            const amount = this.discountMode === 'percent' ? this.subtotal * Math.min(v, 100) / 100 : v;
            return Math.min(amount, this.subtotal + this.tax);
        },
        get total() { return this.round(this.subtotal + this.tax - this.discount); },
        get change() { return Math.max((Number(this.tendered) || 0) - this.total, 0); },
        get short() { return this.method === 'cash' && (Number(this.tendered) || 0) + 0.005 < this.total; },
        get owed() { return Math.max(this.total - Math.min(Number(this.paidNow) || 0, this.total), 0); },

        /* ------------------------------------------------------------ customer */

        get customerMatches() {
            const q = this.customerSearch.trim().toLowerCase();
            const list = q === '' ? this.customers : this.customers.filter((c) =>
                c.name.toLowerCase().includes(q) || (c.phone ?? '').includes(q));
            return list.slice(0, 30);
        },

        openCustomer() {
            this.customerOpen = true;
            this.customerSearch = '';
            this.customerError = '';
            this.newCustomer = { name: '', phone: '' };
            this.$nextTick(() => this.$refs.customerSearch?.focus());
        },

        chooseCustomer(c) {
            this.customer = c;
            this.customerOpen = false;
            this.toast(c ? `Customer: ${c.name}` : 'Walk-in customer');
            if (!c && this.method === 'due') this.method = 'cash';
        },

        async createCustomer() {
            this.customerError = '';
            // Typing digits in the search box pre-fills the phone; letters pre-fill the name.
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            try {
                const res = await fetch(storeCustomerUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.newCustomer),
                });
                const body = await res.json();
                if (!res.ok) {
                    this.customerError = Object.values(body.errors ?? {})[0]?.[0] ?? body.message ?? 'Could not add the customer.';
                    return;
                }
                this.customers.push(body);
                this.customers.sort((a, b) => a.name.localeCompare(b.name));
                this.chooseCustomer(body);
            } catch (e) {
                this.customerError = 'Could not reach the server. Check the connection and try again.';
            }
        },

        quickAmounts() {
            const t = this.total;
            const set = new Set([t]);
            for (const step of [50, 100, 500, 1000]) set.add(Math.ceil(t / step) * step);
            return [...set].filter((v) => v >= t).sort((a, b) => a - b).slice(0, 4);
        },

        /* ----------------------------------------------------------------- pay */

        openPay() {
            if (!this.cart.length) { this.toast('Add something to the sale first'); return; }
            this.cartOpen = false;
            this.payOpen = true;
            this.tendered = '';
            this.paidNow = '';
            if (this.method === 'due' && !this.customer) this.method = 'cash';
            this.$nextTick(() => (this.method === 'cash' ? this.$refs.tendered : this.$refs.reference)?.focus());
        },

        pickMethod(m) {
            this.method = m;
            this.$nextTick(() => ({ cash: this.$refs.tendered, due: this.$refs.paidNow }[m] ?? this.$refs.reference)?.focus());
        },

        complete(event) {
            if ((this.method !== 'due' && this.short) || this.submitting) { event.preventDefault(); return; }
            if (this.method === 'cash' && this.tendered === '') this.tendered = this.total.toFixed(2);
            this.submitting = true;
        },

        /* --------------------------------------------------------------- misc */

        keys(e) {
            if (e.key === 'F4') {
                e.preventDefault();
                this.openCustomer();
            } else if (e.key === 'F2' || (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName))) {
                e.preventDefault();
                this.payOpen = false;
                this.$refs.search?.focus();
                this.$refs.search?.select();
            } else if (e.key === 'F9') {
                e.preventDefault();
                this.openPay();
            } else if (e.key === 'Escape') {
                if (this.customerOpen) this.customerOpen = false;
                else if (this.payOpen) this.payOpen = false;
                else if (this.cartOpen) this.cartOpen = false;
                else this.search = '';
            }
        },

        toast(message) {
            this.flash = message;
            clearTimeout(this._t);
            this._t = setTimeout(() => (this.flash = ''), 1600);
        },

        round(v) { return Math.round(v * 100) / 100; },
        money(v) { return (Number(v) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    };
}

/** Called on the receipt page once a sale is saved. */
export function clearRegisterCart() {
    try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
}
