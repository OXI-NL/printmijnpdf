// Herkomst van de bestelling, zonder browser en zonder verkeer naar buiten.
// Voert resources/views/partials/attribution.blade.php uit met nep-location,
// nep-referrer en een sessionStorage die per tabblad gedeeld wordt.
// Draaien: node --test "tests/js/*.test.mjs"
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/partials/attribution.blade.php', import.meta.url), 'utf8');
const script = source.slice(source.indexOf('<script>') + 8, source.lastIndexOf('</script>'));

/** Eén tabblad: sessionStorage blijft staan tussen paginabezoeken, net als in de browser. */
function tab() {
    const store = new Map();
    const sessionStorage = {
        getItem: k => (store.has(k) ? store.get(k) : null),
        setItem: (k, v) => store.set(k, String(v)),
    };
    return { store, sessionStorage };
}

/** Laad een pagina in het tabblad en geef terug wat het bestelformulier zou meesturen. */
function visit(t, url, referrer = '', opts = {}) {
    const u = new URL(url);
    const outbound = [];
    const ctx = vm.createContext({
        URL,
        URLSearchParams,
        location: { search: u.search, pathname: u.pathname, hostname: u.hostname },
        document: { referrer },
        // Alles wat naar buiten zou gaan, wordt hier opgevangen
        gtag: (...a) => outbound.push(['gtag', a]),
        fetch: (...a) => outbound.push(['fetch', a]),
        navigator: { sendBeacon: (...a) => outbound.push(['beacon', a]) },
    });
    ctx.window = ctx;
    if (opts.storageThrows) {
        Object.defineProperty(ctx, 'sessionStorage', { get() { throw new Error('SecurityError'); } });
    } else {
        ctx.sessionStorage = t.sessionStorage;
    }
    vm.runInContext(script, ctx);
    const A = ctx.PMP_Attribution;
    return {
        A,
        outbound,
        form() {
            const sent = {};
            A.appendToForm({ append: (k, v) => { sent[k] = v; } });
            return sent;
        },
    };
}

const SITE = 'https://printmijnpdf.nl';

test('expliciete UTM blijft precies zoals de link hem meldt', () => {
    const t = tab();
    const p = visit(t, `${SITE}/?utm_source=nieuwsbrief&utm_medium=email&utm_campaign=najaar`);
    assert.deepEqual(p.form(), {
        attribution_utm_source: 'nieuwsbrief',
        attribution_utm_medium: 'email',
        attribution_utm_campaign: 'najaar',
        attribution_landing_page: '/',
    });
});

test('externe Google-verwijzer zonder UTM: verwijzer bewaard, geen verzonnen bron', () => {
    const t = tab();
    const p = visit(t, `${SITE}/pdf-laten-printen`, 'https://www.google.com/');
    assert.deepEqual(p.form(), {
        attribution_landing_page: '/pdf-laten-printen',
        attribution_referrer: 'https://www.google.com/',
    });
});

test('ChatGPT-verwijzer zonder UTM: verwijzer bewaard', () => {
    const t = tab();
    const p = visit(t, `${SITE}/boekje-maken`, 'https://chatgpt.com/');
    assert.equal(p.form().attribution_referrer, 'https://chatgpt.com/');
    assert.equal(p.form().attribution_utm_source, undefined);
});

test('echt ontbrekende bron: niets meegestuurd behalve het instappad, nooit "direct"', () => {
    const t = tab();
    const p = visit(t, `${SITE}/`);
    assert.deepEqual(p.form(), { attribution_landing_page: '/' });
    assert.ok(!JSON.stringify(p.A.getData()).includes('direct'));
    assert.ok(!JSON.stringify(p.A.getData()).includes('none'));
});

test('landingspagina → homepage: eerste instappad en bron blijven staan', () => {
    const t = tab();
    visit(t, `${SITE}/boekje-maken?utm_source=chatgpt.com`, 'https://chatgpt.com/');
    // Klik op "Start je bestelling": homepage, verwijzer is de eigen site, geen UTM in de URL
    const home = visit(t, `${SITE}/#upload`, `${SITE}/boekje-maken?utm_source=chatgpt.com`);
    assert.deepEqual(home.form(), {
        attribution_utm_source: 'chatgpt.com',
        attribution_landing_page: '/boekje-maken',
        attribution_referrer: 'https://chatgpt.com/',
    });
});

test('Google organisch → landingspagina → homepage: verwijzer en instap van de landingspagina', () => {
    const t = tab();
    visit(t, `${SITE}/pdf-naar-boekje`, 'https://www.google.nl/');
    visit(t, `${SITE}/prijzen`, `${SITE}/pdf-naar-boekje`);
    const home = visit(t, `${SITE}/`, `${SITE}/prijzen`);
    assert.equal(home.form().attribution_landing_page, '/pdf-naar-boekje');
    assert.equal(home.form().attribution_referrer, 'https://www.google.nl/');
});

test('herhaalbezoek in hetzelfde tabblad overschrijft de eerste instap niet', () => {
    const t = tab();
    visit(t, `${SITE}/scriptie-printen`, 'https://www.google.com/');
    const again = visit(t, `${SITE}/?utm_source=facebook&utm_medium=paid_social`, 'https://l.facebook.com/');
    assert.deepEqual(again.form(), {
        attribution_landing_page: '/scriptie-printen',
        attribution_referrer: 'https://www.google.com/',
    });
});

test('herhaalbezoek in een nieuw tabblad is een nieuwe instap', () => {
    visit(tab(), `${SITE}/scriptie-printen`, 'https://www.google.com/');
    const fresh = visit(tab(), `${SITE}/?utm_source=chatgpt.com`);
    assert.deepEqual(fresh.form(), { attribution_utm_source: 'chatgpt.com', attribution_landing_page: '/' });
});

test('klik-ID\'s gaan alle drie mee, als ruwe waarde', () => {
    const t = tab();
    const p = visit(t, `${SITE}/?gclid=abc&fbclid=def&msclkid=ghi`);
    const f = p.form();
    assert.equal(f.attribution_gclid, 'abc');
    assert.equal(f.attribution_fbclid, 'def');
    assert.equal(f.attribution_msclkid, 'ghi');
    assert.equal(f.attribution_utm_source, undefined, 'geen bron afgeleid uit een klik-ID');
});

test('instappad zonder querystring: geen andere URL-parameters in de bestelling', () => {
    const t = tab();
    const p = visit(t, `${SITE}/prijzen?email=jan%40example.com&utm_source=x`);
    const f = p.form();
    assert.equal(f.attribution_landing_page, '/prijzen');
    assert.ok(!JSON.stringify(f).includes('example.com'));
});

test('tabblad dat nog gegevens van de vorige versie heeft, houdt die', () => {
    const t = tab();
    t.store.set('pmp_landing_page', '/');
    t.store.set('utm_source', 'chatgpt.com');
    const p = visit(t, `${SITE}/`, 'https://www.google.com/');
    assert.deepEqual(p.form(), { attribution_utm_source: 'chatgpt.com', attribution_landing_page: '/' });
});

test('geen sessionStorage (privémodus/geblokkeerd): geen fout, niets meegestuurd', () => {
    const p = visit(tab(), `${SITE}/?utm_source=x`, 'https://www.google.com/', { storageThrows: true });
    assert.deepEqual(p.form(), {});
});

test('het script stuurt zelf niets naar buiten en niets naar GA4', () => {
    const t = tab();
    const p = visit(t, `${SITE}/boekje-maken?utm_source=chatgpt.com&gclid=abc`, 'https://chatgpt.com/');
    p.form();
    assert.deepEqual(p.outbound, []);
});
