/**
 * Keyboard-only selling on the register (resources/js/register.js).
 * Run: npm run test:js   (plain Node, no browser or extra packages)
 * Each test drives the register the way a cashier with a keyboard or a
 * barcode scanner would.
 */
import assert from 'node:assert/strict';
import register from '../../resources/js/register.js';

globalThis.localStorage = { getItem: () => null, setItem() {} };
let focused = null;
const fakeEl = (name) => ({ name, focus() { focused = name; }, select() {}, scrollIntoView() {}, value: '' });
globalThis.document = { activeElement: null, querySelector: (sel) => fakeEl(sel) };
globalThis.confirm = () => true;

function make(opts = {}) {
    const r = register({
        products: [
            { id: 1, name: 'Milk 1L', sku: '5901234123457', price: 2, tax: null, track: false, stock: 0 },
            { id: 2, name: 'Milk 500ml', sku: 'MLK-500', price: 1.2, tax: null, track: false, stock: 0 },
            { id: 3, name: 'Bread', sku: 'BRD', price: 1.5, tax: null, track: false, stock: 0 },
        ],
        methods: ['cash', 'card', 'mobile_wallet'],
        customers: [{ id: 7, name: 'Rahim', phone: '017' }, { id: 8, name: 'Karim', phone: '018' }],
        ...opts,
    });
    const watchers = {};
    r.$watch = (k, fn) => { watchers[k] = fn; };
    r.$nextTick = (fn) => fn();
    r.$refs = { search: fakeEl('search'), discount: fakeEl('discount'), customerSearch: fakeEl('customerSearch'), tendered: fakeEl('tendered'), reference: fakeEl('reference'), paidNow: fakeEl('paidNow') };
    r.init();
    // emulate Alpine reactivity for the watched props used in tests
    const set = (k, v) => { r[k] = v; watchers[k]?.(v); };
    return { r, set };
}
const key = (k, extra = {}) => { let prevented = false; return { key: k, preventDefault() { prevented = true; }, get prevented() { return prevented; }, target: { value: '' }, ...extra }; };
const qty = (r, id) => r.cart.find((l) => l.id === id)?.qty ?? 0;
let n = 0; const t = (name, fn) => { fn(); n++; console.log('  ✓', name); };

t('scanner: exact barcode + Enter adds it and clears the box', () => {
    const { r, set } = make();
    set('search', '5901234123457'); r.submitSearch();
    assert.equal(qty(r, 1), 1); assert.equal(r.search, '');
});
t('3*barcode adds three', () => {
    const { r, set } = make();
    set('search', '3*5901234123457'); r.submitSearch();
    assert.equal(qty(r, 1), 3);
});
t('3*name also works, and 2 * with spaces', () => {
    const { r, set } = make();
    set('search', '3*bread'); r.submitSearch();
    set('search', '2 * bread'); r.submitSearch();
    assert.equal(qty(r, 3), 5);
});
t('unknown barcode is never guessed into a partial match', () => {
    const { r, set } = make();
    set('search', '590123'); r.submitSearch();
    assert.equal(r.cart.length, 0);
    assert.match(r.flash, /No product with barcode 590123/);
});
t('several matches: Enter adds the first, ↓ Enter adds the second', () => {
    const { r, set } = make();
    set('search', 'milk'); r.submitSearch();
    assert.equal(qty(r, 1), 1);
    set('search', 'milk');
    const e = key('ArrowDown'); r.searchKeys(e); assert.ok(e.prevented);
    assert.equal(r.active, 1);
    r.submitSearch();
    assert.equal(qty(r, 2), 1);
});
t('↓ stops at the last result, ↑ at the first; typing resets the highlight', () => {
    const { r, set } = make();
    set('search', 'milk');
    r.searchKeys(key('ArrowDown')); r.searchKeys(key('ArrowDown')); r.searchKeys(key('ArrowDown'));
    assert.equal(r.active, 1);
    r.searchKeys(key('ArrowUp')); r.searchKeys(key('ArrowUp'));
    assert.equal(r.active, 0);
    r.searchKeys(key('ArrowDown')); set('search', 'milk 5');
    assert.equal(r.active, 0);
});
t('empty box: + and − change the last added line; − at 1 removes it', () => {
    const { r, set } = make();
    set('search', 'BRD'); r.submitSearch();
    const plus = key('+'); r.searchKeys(plus); assert.ok(plus.prevented);
    r.searchKeys(key('+'));
    assert.equal(qty(r, 3), 3);
    r.searchKeys(key('-')); r.searchKeys(key('-')); r.searchKeys(key('-'));
    assert.equal(r.cart.length, 0);
});
t('+ and − type normally while the box has text', () => {
    const { r, set } = make();
    set('search', 'BRD'); r.submitSearch();
    set('search', 'x');
    const e = key('+'); r.searchKeys(e);
    assert.ok(!e.prevented); assert.equal(qty(r, 3), 1);
});
t('PageUp/PageDown move the highlighted line; Delete removes it', () => {
    const { r, set } = make();
    for (const c of ['5901234123457', 'MLK-500', 'BRD']) { set('search', c); r.submitSearch(); }
    assert.equal(r.selectedLine.id, 3);
    r.searchKeys(key('PageUp')); assert.equal(r.selectedLine.id, 2);
    r.searchKeys(key('PageUp')); r.searchKeys(key('PageUp')); assert.equal(r.selectedLine.id, 3); // wraps
    r.searchKeys(key('PageDown')); assert.equal(r.selectedLine.id, 1);
    r.searchKeys(key('Delete'));
    assert.deepEqual(r.cart.map((l) => l.id), [2, 3]);
    assert.equal(r.selectedLine.id, 3);
});
t('F3 focuses the highlighted line qty; Enter there sets it and returns to search', () => {
    const { r, set } = make();
    set('search', 'BRD'); r.submitSearch();
    r.keys(key('F3'));
    assert.equal(focused, '[data-qty-for="3"]');
    const line = r.selectedLine;
    r.qtyKeys(key('Enter', { target: { value: '12' } }), line);
    assert.equal(line.qty, 12); assert.equal(focused, 'search');
});
t('F8 opens discount; % flips to percent; Enter goes back', () => {
    const { r, set } = make();
    set('search', 'BRD'); r.submitSearch(); r.searchKeys(key('+')); // 2 x 1.5 = 3.00
    r.keys(key('F8'));
    assert.ok(r.showDiscount); assert.equal(focused, 'discount');
    r.discountInput = '10';
    const pct = key('%'); r.discountKeys(pct); assert.ok(pct.prevented);
    assert.equal(r.discountMode, 'percent');
    assert.equal(r.discount, 0.3);
    r.discountKeys(key('Enter')); assert.equal(focused, 'search');
});
t('F4 customer picker: ↓ ↓ Enter picks the 2nd customer; search puts the first match on top', () => {
    const { r, set } = make();
    r.keys(key('F4'));
    assert.ok(r.customerOpen); assert.equal(r.customerActive, 0);
    r.customerKeys(key('ArrowDown')); r.customerKeys(key('ArrowDown'));
    r.customerKeys(key('Enter'));
    assert.equal(r.customer.name, 'Karim'); // row 0 walk-in, 1 Rahim, 2 Karim
});
t('customer search: typing selects the first match, Enter picks it', () => {
    const { r, set } = make();
    r.keys(key('F4'));
    set('customerSearch', 'kar');
    assert.equal(r.customerActive, 1);
    r.customerKeys(key('Enter'));
    assert.equal(r.customer.name, 'Karim');
    assert.ok(!r.customerOpen); assert.equal(focused, 'search');
});
t('F9 opens pay; PgDn cycles methods; Pay later only with a customer', () => {
    const { r, set } = make();
    set('search', 'BRD'); r.submitSearch();
    r.keys(key('F9')); assert.ok(r.payOpen); assert.equal(r.method, 'cash');
    r.keys(key('PageDown')); assert.equal(r.method, 'card');
    r.keys(key('PageDown')); r.keys(key('PageDown')); assert.equal(r.method, 'cash'); // wraps, no 'due'
    r.keys(key('PageUp')); assert.equal(r.method, 'mobile_wallet');
    r.customer = { id: 7, name: 'Rahim' };
    r.keys(key('PageDown')); assert.equal(r.method, 'due');
});
t('F9 on an empty sale does not open pay', () => {
    const { r } = make();
    r.keys(key('F9')); assert.ok(!r.payOpen);
});
t('Enter with an empty cash box completes as exact cash; short cash is blocked with a message', () => {
    const { r, set } = make();
    set('search', 'BRD'); r.submitSearch();
    r.keys(key('F9'));
    const ok = key('submit'); r.complete(ok);
    assert.ok(!ok.prevented); assert.equal(r.tendered, '1.50'); assert.ok(r.submitting);

    const { r: r2, set: set2 } = make();
    set2('search', 'BRD'); r2.submitSearch(); r2.keys(key('F9'));
    r2.tendered = '1';
    const short = key('submit'); r2.complete(short);
    assert.ok(short.prevented); assert.match(r2.flash, /less than the total/);
});
t('? toggles help when the search box is empty, but types normally otherwise; Esc closes', () => {
    const { r, set } = make();
    document.activeElement = r.$refs.search; r.$refs.search.tagName = 'INPUT';
    const q = key('?'); r.keys(q); assert.ok(q.prevented); assert.ok(r.helpOpen);
    r.keys(key('Escape')); assert.ok(!r.helpOpen);
    set('search', 'mi');
    const q2 = key('?'); r.keys(q2); assert.ok(!q2.prevented); assert.ok(!r.helpOpen);
    document.activeElement = null;
});
console.log(`\n${n} passed`);
