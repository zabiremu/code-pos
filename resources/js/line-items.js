/**
 * Line-item editor for purchases and GRNs.
 *
 * Usage: x-data="lineItems({ products, lines, extras })"
 *   products: [{ id, label, cost, unit, decimal }]
 *   lines:    [{ product_id, quantity, unit_cost, purchase_item_id?, ordered? }]
 *   extras:   { discount, tax, shipping } or null (GRNs have no extras)
 *
 * The server recalculates every total; these are only for the live preview.
 */
export default function lineItems({ products = [], lines = [], extras = null } = {}) {
    const blank = () => ({ key: crypto.randomUUID?.() ?? String(Math.random()), product_id: '', quantity: 1, unit_cost: 0, purchase_item_id: null, ordered: null });

    return {
        products,
        rows: lines.length ? lines.map((l) => ({ ...blank(), ...l })) : [blank()],
        extras: extras ? { discount: 0, tax: 0, shipping: 0, ...extras } : null,

        product(id) {
            return this.products.find((p) => String(p.id) === String(id));
        },

        pick(row) {
            // Pre-fill the last cost paid, but never overwrite a cost the user typed.
            const p = this.product(row.product_id);
            if (p && (!row.unit_cost || Number(row.unit_cost) === 0)) {
                row.unit_cost = p.cost;
            }
        },

        add() {
            this.rows.push(blank());
            this.$nextTick(() => {
                const selects = this.$root.querySelectorAll('select[data-line-product]');
                selects[selects.length - 1]?.focus();
            });
        },

        remove(index) {
            this.rows.splice(index, 1);
            if (!this.rows.length) this.rows.push(blank());
        },

        lineTotal(row) {
            return (Number(row.quantity) || 0) * (Number(row.unit_cost) || 0);
        },

        get subtotal() {
            return this.rows.reduce((sum, row) => sum + this.lineTotal(row), 0);
        },

        get total() {
            if (!this.extras) return this.subtotal;
            const e = this.extras;
            return Math.max(this.subtotal - (Number(e.discount) || 0) + (Number(e.tax) || 0) + (Number(e.shipping) || 0), 0);
        },

        get itemCount() {
            return this.rows.filter((r) => r.product_id && Number(r.quantity) > 0).length;
        },

        money(value) {
            return (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        step(row) {
            return this.product(row.product_id)?.decimal === false ? '1' : '0.001';
        },
    };
}
