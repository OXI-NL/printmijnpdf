// Trechterevents van de bestelpagina, zonder browser en zonder verkeer naar Google.
// Voert resources/views/partials/analytics-helper.blade.php uit met een nep-gtag.
// Draaien: node --test "tests/js/*.test.mjs"
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/partials/analytics-helper.blade.php', import.meta.url), 'utf8');
const script = source.slice(source.indexOf('<script>') + 8, source.lastIndexOf('</script>'));

function page() {
    const events = [];
    const listeners = {};
    // Zoals in de browser is window het globale object
    const window = vm.createContext({
        addEventListener: (name, fn) => { listeners[name] = fn; },
        gtag: (cmd, name, params) => events.push([name, params]),
    });
    window.window = window;
    vm.runInContext(script, window);
    const A = window.PMP_Analytics;
    // Zoals welcome.blade.php het aanroept
    A.upload = () => A.trackUpload(20, 1.2, { finishing: 'loose|double', format: 'A4', value: 13.0 });
    return { A, names: () => events.map(e => e[0]), events, pagehide: () => listeners.pagehide() };
}

const FUNNEL = ['pdf_upload', 'add_shipping_info', 'add_payment_info'];

function inFunnelOrder(names) {
    // Gesloten trechter: elke stap komt (direct of later) na de vorige
    let at = -1;
    return FUNNEL.every(step => (at = names.indexOf(step, at + 1)) !== -1);
}

test('eerst bezorgkeuze, dan klantgegevens', () => {
    const p = page();
    p.A.upload();
    p.A.trackShipping('delivery', 19.75);
    p.A.trackBeginCheckout(19.75);
    p.A.trackPaymentStart(19.75, 'delivery');
    assert.deepEqual(p.names(), ['pdf_upload', 'view_item', 'add_shipping_info', 'begin_checkout', 'add_payment_info']);
    assert.ok(inFunnelOrder(p.names()));
});

test('eerst klantgegevens, dan bezorgkeuze (de route uit de controle van Codex)', () => {
    const p = page();
    p.A.upload();
    p.A.trackBeginCheckout(19.75);
    p.A.trackShipping('pickup', 13.0);
    p.A.trackBeginCheckout(13.0); // verder typen
    p.A.trackPaymentStart(13.0, 'pickup');
    assert.deepEqual(p.names(), ['pdf_upload', 'view_item', 'begin_checkout', 'add_shipping_info', 'add_payment_info']);
    assert.ok(inFunnelOrder(p.names()), 'klant valt niet uit de trechter');
});

test('wisselen tussen afhalen en bezorgen meet elke echte wissel, niet elke klik', () => {
    const p = page();
    p.A.upload();
    p.A.trackShipping('delivery', 19.75);
    p.A.trackShipping('delivery', 19.75);
    p.A.trackShipping('pickup', 13.0);
    p.A.trackShipping('delivery', 19.75);
    p.A.trackPaymentStart(19.75, 'delivery');
    const tiers = p.events.filter(e => e[0] === 'add_shipping_info').map(e => e[1].shipping_tier);
    assert.deepEqual(tiers, ['delivery', 'pickup', 'delivery']);
    assert.equal(p.events.find(e => e[0] === 'add_payment_info')[1].shipping_tier, 'delivery');
    assert.ok(inFunnelOrder(p.names()));
});

test('nieuwe PDF na een eerdere bezorgkeuze: stap 3 komt alsnog vóór de betaalstart', () => {
    const p = page();
    p.A.upload();
    p.A.trackShipping('pickup', 13.0);
    p.A.upload(); // andere PDF; de bezorgkeuze in het formulier blijft staan
    p.A.trackPaymentStart(13.0, 'pickup');
    const names = p.names();
    const secondUpload = names.lastIndexOf('pdf_upload');
    assert.ok(inFunnelOrder(names.slice(secondUpload)));
});

test('geen trechterevents zonder geanalyseerde PDF', () => {
    const p = page();
    p.A.trackShipping('delivery', 6.75);
    p.A.trackBeginCheckout(6.75);
    p.pagehide();
    assert.deepEqual(p.names(), []);
});

test('vertrek zonder betaalstart geeft één checkout_exit met de laatste fase', () => {
    const p = page();
    p.A.upload();
    p.A.trackBeginCheckout(13.0);
    p.A.trackShipping('pickup', 13.0);
    p.pagehide();
    p.pagehide();
    const exits = p.events.filter(e => e[0] === 'checkout_exit');
    assert.equal(exits.length, 1);
    assert.equal(exits[0][1].checkout_stage, 'shipping_selected');
});

test('na de betaalstart geen checkout_exit (doorverwijzing naar Mollie)', () => {
    const p = page();
    p.A.upload();
    p.A.trackShipping('delivery', 19.75);
    p.A.trackPaymentStart(19.75, 'delivery');
    p.pagehide();
    assert.ok(!p.names().includes('checkout_exit'));
});
