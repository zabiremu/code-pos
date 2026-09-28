/**
 * The register (resources/views/pos/register/index.blade.php).
 *
 * The cart lives here and in localStorage (so a refresh or a failed
 * checkout doesn't lose it). "Complete sale" posts the cart once; the
 * server re-prices everything, so these totals are only a preview.
 *
 * A whole sale can be rung up from the keyboard (or a barcode scanner,
 * which types like a keyboard). See SHORTCUTS below / press ? on the page.
 */

/** A search/scan that looks like a barcode (digits only, 6+). Never guessed at. */
const BARCODE = /^\d{6,}$/;

/** "3*milk" or "3*5901234123457" = add 3. */
const QTY_PREFIX = /^(\d{1,4})\s*\*\s*(.*)$/;
const STORAGE_KEY = 'shoppulse_register_cart';

export default function register({ products = [], defaultTax = 0, oldTendered = null, customers = [], storeCustomerUrl = '', methods = ['cash'] } = {}) {
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
        methods,
        active: 0,          // highlighted search result
        searchFocused: false,
        selected: null,     // product id of the highlighted cart line
        customerActive: 0,  // highlighted row in the customer picker (0 = walk-in)
        helpOpen: false,

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
            this.$watch('search', () => { this.active = 0; });
            this.$watch('customerSearch', (q) => { this.customerActive = q.trim() === '' ? 0 : 1; });
            this.selected = this.cart.at(-1)?.id ?? null;
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

        /** The search box split into an optional "N*" quantity and the actual term. */
        get parsedSearch() {
            const raw = this.search.trim();
            const m = raw.match(QTY_PREFIX);
            return m ? { qty: Math.max(Number(m[1]), 1), term: m[2].trim() } : { qty: 1, term: raw };
        },

        get filtered() {
            const q = this.parsedSearch.term.toLowerCase();
            return this.products.filter((p) =>
                (this.category === null || p.category_id === this.category) &&
                (q === '' || p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q)));
        },

        /* ---------------------------------------------------------------- cart */

        add(product, qty = 1) {
            const line = this.cart.find((l) => l.id === product.id);
            if (line) {
                line.qty = Math.min(line.qty + qty, 9999);
            } else {
                this.cart.push({ id: product.id, qty: Math.min(qty, 9999) });
            }
            this.selected = product.id;
            this.toast(qty > 1 ? `${qty} × ${product.name} added` : `${product.name} added`);
        },

        /** The line +/-/Delete/F3 act on: the one last added or picked with PageUp/PageDown. */
        get selectedLine() {
            return this.cart.find((l) => l.id === this.selected) ?? this.cart.at(-1) ?? null;
        },

        moveSelection(step) {
            if (!this.cart.length) return;
            const i = Math.max(this.cart.indexOf(this.selectedLine), 0);
            this.selected = this.cart[(i + step + this.cart.length) % this.cart.length].id;
            this.$nextTick(() => document.querySelector(`[data-line="${this.selected}"]`)?.scrollIntoView({ block: 'nearest' }));
        },

        /** F3: type an exact quantity for the selected line; Enter/Esc goes back to search. */
        editQty() {
            const line = this.selectedLine;
            if (!line) { this.toast('The sale is empty'); return; }
            this.$nextTick(() => {
                const input = document.querySelector(`[data-qty-for="${line.id}"]`);
                input?.focus();
                input?.select();
            });
        },

        inc(line) { line.qty++; },
        dec(line) { line.qty > 1 ? line.qty-- : this.removeLine(line); },
        setQty(line, value) {
            const n = Math.floor(Number(value));
            line.qty = Number.isFinite(n) && n > 0 ? Math.min(n, 9999) : 1;
        },
        removeLine(line) {
            this.cart = this.cart.filter((l) => l !== line);
            if (this.selected === line.id) this.selected = this.cart.at(-1)?.id ?? null;
        },

        clearCart() {
            if (this.cart.length && !confirm('Clear the whole sale?')) return;
            this.cart = [];
            this.discountInput = '';
            this.showDiscount = false;
            this.customer = null;
            this.selected = null;
            this.focusSearch();
        },

        /**
         * Enter in the search box. An exact SKU/barcode match wins; otherwise
         * the highlighted result (↑/↓) is added. A scanned code that isn't a
         * product is never "guessed" into a partial match.
         */
        submitSearch() {
            const { qty, term } = this.parsedSearch;
            const q = term.toLowerCase();
            if (!q) return;
            const exact = this.products.find((p) => (p.sku ?? '').toLowerCase() === q);
            const hit = exact ?? (BARCODE.test(q) ? null : this.filtered[this.active] ?? null);
            if (hit) {
                this.add(hit, qty);
                this.search = '';
            } else {
                this.toast(BARCODE.test(q) ? `No product with barcode ${term}` : `Nothing matches "${term}"`);
            }
        },

        /** Keys typed in the search box: result navigation, and cart-line keys when it's empty. */
        searchKeys(e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                if (!this.filtered.length) return;
                e.preventDefault();
                const step = e.key === 'ArrowDown' ? 1 : -1;
                this.active = Math.min(Math.max(this.active + step, 0), this.filtered.length - 1);
                this.$nextTick(() => document.querySelector(`[data-result="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
                return;
            }
            if (this.search !== '') return;
            const line = this.selectedLine;
            if (e.key === '+' && line) { e.preventDefault(); this.inc(line); }
            else if (e.key === '-' && line) { e.preventDefault(); this.dec(line); }
            else if (e.key === 'Delete' && line) { e.preventDefault(); this.removeLine(line); this.toast('Line removed'); }
            else if (e.key === 'PageDown') { e.preventDefault(); this.moveSelection(1); }
            else if (e.key === 'PageUp') { e.preventDefault(); this.moveSelection(-1); }
        },

        /** Enter / Esc in a cart line's quantity box. */
        qtyKeys(e, line) {
            if (e.key === 'Enter' || e.key === 'Escape') {
                e.preventDefault();
                if (e.key === 'Enter') this.setQty(line, e.target.value);
                else e.target.value = line.qty;
                this.focusSearch();
            }
        },

        focusSearch() {
            this.$nextTick(() => { this.$refs.search?.focus(); this.$refs.search?.select(); });
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
        openDiscount() {
            this.showDiscount = true;
            this.$nextTick(() => { this.$refs.discount?.focus(); this.$refs.discount?.select(); });
        },

        /** In the discount box: % flips amount/percent, Enter or Esc goes back to search. */
        discountKeys(e) {
            if (e.key === '%') { e.preventDefault(); this.discountMode = this.discountMode === 'percent' ? 'amount' : 'percent'; }
            else if (e.key === 'Enter' || e.key === 'Escape') { e.preventDefault(); this.focusSearch(); }
        },

        get discount() {
            const v = Math.max(Number(this.discountInput) || 0, 0);
            const amount = this.discountMode === 'percent' ? this.subtotal * Math.min(v, 100) / 100 : v;
            return Math.min(amount, this.subtotal + this.tax);
        },
        get total() { return this.round(this.subtotal + this.tax - this.discount); },
        get change() { return Math.max((Number(this.tendered) || 0) - this.total, 0); },
        /** An empty cash box means "exact amount" (it's filled in on submit), so it's never short. */
        get short() { return this.method === 'cash' && this.tendered !== '' && (Number(this.tendered) || 0) + 0.005 < this.total; },
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
            this.customerActive = this.customer ? this.customerMatches.findIndex((c) => c.id === this.customer.id) + 1 : 0;
            this.$nextTick(() => this.$refs.customerSearch?.focus());
        },

        /** ↑/↓/Enter in the customer search. Row 0 is walk-in, then the matches. */
        customerKeys(e) {
            const rows = this.customerMatches.length + 1;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                this.customerActive = Math.min(Math.max(this.customerActive + (e.key === 'ArrowDown' ? 1 : -1), 0), rows - 1);
                this.$nextTick(() => document.querySelector(`[data-customer-row="${this.customerActive}"]`)?.scrollIntoView({ block: 'nearest' }));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.chooseCustomer(this.customerActive === 0 ? null : this.customerMatches[this.customerActive - 1] ?? null);
            }
        },

        chooseCustomer(c) {
            this.customer = c;
            this.customerOpen = false;
            this.focusSearch();
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

        /** PageUp/PageDown in the pay screen. Pay later only exists with a customer. */
        cycleMethod(step) {
            const list = this.customer ? [...this.methods, 'due'] : this.methods;
            const i = list.indexOf(this.method);
            this.pickMethod(list[(i + step + list.length) % list.length]);
        },

        pickMethod(m) {
            this.method = m;
            this.$nextTick(() => ({ cash: this.$refs.tendered, due: this.$refs.paidNow }[m] ?? this.$refs.reference)?.focus());
        },

        complete(event) {
            if (this.submitting) { event.preventDefault(); return; }
            if (this.method !== 'due' && this.short) {
                event.preventDefault();
                this.toast('Cash received is less than the total');
                return;
            }
            if (this.method === 'cash' && this.tendered === '') this.tendered = this.total.toFixed(2);
            this.submitting = true;
        },

        /* --------------------------------------------------------------- misc */

        keys(e) {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if (e.key === 'F1' || (e.key === '?' && (!typing || (document.activeElement === this.$refs.search && this.search === '')))) {
                e.preventDefault();
                this.helpOpen = !this.helpOpen;
            } else if (e.key === 'F4') {
                e.preventDefault();
                this.payOpen = false;
                this.openCustomer();
            } else if (e.key === 'F2' || (e.key === '/' && !typing)) {
                e.preventDefault();
                this.payOpen = false;
                this.customerOpen = false;
                this.helpOpen = false;
                this.focusSearch();
            } else if (e.key === 'F3') {
                e.preventDefault();
                this.editQty();
            } else if (e.key === 'F8') {
                e.preventDefault();
                this.openDiscount();
            } else if (e.key === 'F9') {
                e.preventDefault();
                this.customerOpen = false;
                this.openPay();
            } else if (this.payOpen && (e.key === 'PageDown' || e.key === 'PageUp')) {
                e.preventDefault();
                this.cycleMethod(e.key === 'PageDown' ? 1 : -1);
            } else if (e.key === 'Escape') {
                if (this.helpOpen) this.helpOpen = false;
                else if (this.customerOpen) this.customerOpen = false;
                else if (this.payOpen) this.payOpen = false;
                else if (this.cartOpen) this.cartOpen = false;
                else this.search = '';
                this.focusSearch();
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
