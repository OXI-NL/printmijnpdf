# Meting PrintMijnPDF (GA4)

Bijgewerkt: 6 november 2026 (na controle Codex, PR 3). Meet-ID `G-9F48GD4CX5` (config `analytics.ga4_measurement_id`, env `GA4_MEASUREMENT_ID`).

**De orderadministratie is de bron voor omzet.** GA4 is ondersteunend (kanaal, trechter).
Financiële controletelling: `php artisan orders:report --from=2026-11-06 --to=2026-11-12 [--details]`
(unieke betaalde orders op orderdatum, bruto incl. btw, terugbetalingen op creditnotadatum, testbetalingen uitgesloten).

## Trechter

| # | Fase | Event | Trigger (precies) | Telt opnieuw? |
|---|------|-------|-------------------|---------------|
| 1 | Bezoek | `page_view` / `session_start` | GA4 automatisch | – |
| 2 | PDF geanalyseerd, prijs in beeld | `pdf_upload` + `view_item` | `/api/calculate-price` gaf `success`; afwerking en prijs verschijnen direct | Eén keer per geanalyseerde PDF |
| 3 | Verzenden of afhalen | `add_shipping_info` (`shipping_tier`: `delivery`/`pickup`) | Klik op de bezorgoptie | Eerste keuze en elke echte wissel per PDF |
| 4 | Betaalstart | `add_payment_info` (`payment_type: ideal`, `shipping_tier`) | Server heeft de order gemaakt en de klant gaat naar Mollie | Eén keer per poging |
| 5 | Betaalde order | `purchase` | Bevestigingspagina van een betaalde order die de aankoop bij de server claimt | Eén keer per ordernummer (zie onder) |

Niet in de trechter, wel gemeten: `begin_checkout` = eerste invoer in de klantgegevens (één keer per PDF).

Prijs en afwerking staan meteen na de analyse in beeld; daarom vallen fase 2 en "afwerking/prijs" samen.
`view_item.value` is de prijs met de standaardafwerking, zonder bezorgkosten.

### Toegestane volgorden

Na de upload mag de klant de klantgegevens en de bezorgkeuze in elke volgorde invullen, en wisselen tussen
bezorgen en afhalen. Bestellen kan pas met een bezorgkeuze. Daarom:

- `add_shipping_info` komt altijd vóór `add_payment_info`. Is de keuze voor de huidige PDF nog niet gemeten
  (bijvoorbeeld gekozen vóór een nieuwe upload), dan stuurt de betaalstart eerst alsnog `add_shipping_info`.
- `begin_checkout` kan vóór óf na `add_shipping_info` komen. Daarom staat hij níet in de gesloten trechter;
  anders valt wie eerst zijn adres invult, ten onrechte uit.

| Route van de klant | Events (volgorde) |
|---|---|
| Bezorgkeuze, dan gegevens | `pdf_upload`, `view_item`, `add_shipping_info`, `begin_checkout`, `add_payment_info` |
| Gegevens, dan bezorgkeuze | `pdf_upload`, `view_item`, `begin_checkout`, `add_shipping_info`, `add_payment_info` |
| Bezorgen → afhalen → bezorgen | elke wissel een `add_shipping_info`; de betaalstart draagt de laatste `shipping_tier` |

Getest in `tests/js/analytics-helper.test.mjs` (`node --test "tests/js/*.test.mjs"`, draait ook in CI) op dezelfde
helper die de bestelpagina laadt (`resources/views/partials/analytics-helper.blade.php`).
In een trechterverkenning telt GA4 iemand per stap maar één keer.

### Fouten en vertrek (geen trechterstappen)

| Event | Wanneer | Parameter |
|-------|---------|-----------|
| `pdf_upload_failed` | Upload of analyse lukt niet | `error_type`: `not_pdf`, `too_large`, `format_rejected`, `analysis_failed`, `server`, `network` |
| `payment_start_failed` | Bestellen lukt niet | `error_type`: `validation`, `server`, `network` |
| `payment_pending` | Terug van Mollie, betaling nog niet bevestigd | `transaction_id` (één keer per tab) |
| `payment_failed` | Terug van Mollie, betaling geannuleerd/mislukt/verlopen | `transaction_id` (één keer per tab) |
| `checkout_exit` | Pagina verlaten met een geanalyseerde PDF, zonder betaalstart | `checkout_stage`, `seconds_since_upload` |
| `select_finishing` | Andere afwerking of zijdigheid gekozen (alleen bij een echte wijziging) | `finishing_type`, `print_side`, `quantity` |
| `landing_cta_click` | Klik op een uploadknop op een landingspagina | `cta_location` (`hero`, `prijs`, `onderaan`), `landing_page` |

Er gaan nooit bestandsnamen, PDF-inhoud, namen, e-mailadressen of adressen naar GA4.

Vervallen: `cart_abandonment` (ging ook af bij de doorverwijzing naar Mollie) en `select_item` (dubbel met `select_finishing`).

## Aankoop: drie gescheiden statussen

`App\Services\PurchaseTracking`. Per order staat apart vast:

| Status | Kolom | Wat het wél en níet zegt |
|---|---|---|
| Browser heeft het event afgehandeld | `analytics_purchase_handled_at` | gtag riep `event_callback` aan en de pagina meldde dat. **Geen ontvangstbewijs**: gtag doet dat ook als alleen het verzoek naar Google geblokkeerd is of na een time-out. Dient alleen om dubbeltelling te voorkomen. |
| Vóór de nieuwe meting | `analytics_purchase_legacy` | Order bestond al bij de uitrol. Onbekend of de oude code hem mat. Wordt niet opnieuw gestuurd (kan dubbel tellen) en telt nergens als gemeten. |
| Teruggevonden in GA4 | `analytics_purchase_ga4_count`, `…_ga4_checked_at` | Alleen gezet door `analytics:import-ga4` uit een GA4-export: 1 = één keer, 2+ = dubbel, 0 = ontbreekt. Leeg = niet gecontroleerd. |

Werking:

1. De bevestigingspagina krijgt het `purchase`-event alleen als de server de aankoop atomair claimt: de order is
   betaald, geen legacy, nog niet afgehandeld, niet al in GA4 teruggevonden, en er loopt geen claim van minder
   dan 10 minuten.
2. Na `event_callback` meldt de pagina `POST /bestelling/{nr}/aankoop-gemeten` met een HMAC-token. Daarna stuurt
   geen pagina dit ordernummer nog.
3. Komt die melding niet, dan verloopt de claim na 10 minuten en mag een volgend bezoek het opnieuw proberen.

### Wat er gebeurt per storing

Getest: A en B in een proefpagina met de echte gtag.js en een nep-meet-ID (verzoeken naar Google in de pagina
geblokkeerd, er ging geen hit weg); C en D in `tests/Feature/PurchaseTrackingTest.php`.

| Situatie | Browser | Status bij ons | Gevolg in GA4 | Herstel |
|---|---|---|---|---|
| A. gtag.js geheel geblokkeerd | Event blijft in `dataLayer`, geen callback (8 s gewacht) | niet afgehandeld; claim verloopt | ontbreekt | Volgend bezoek na 10 min probeert opnieuw. Komt de klant niet terug: zichtbaar met `orders:report --missing`. |
| B. Alleen het verzoek naar Google geblokkeerd | gtag.js laadt, verzoek geblokkeerd, callback 2 ms later | afgehandeld (géén ontvangst) | ontbreekt | Opnieuw proberen vanuit dezelfde browser helpt niet (zelfde blokkade). Zichtbaar als "ontbreekt" na `analytics:import-ga4`. |
| C. Melding aan onze server mislukt (netwerk) | Event verstuurd, melding komt niet aan | niet afgehandeld; claim verloopt | waarschijnlijk ontvangen | Volgend bezoek na 10 min stuurt opnieuw: **kan dubbel tellen**. Zichtbaar als "dubbel" na de import. |
| D. Tabblad dicht | Vóór gtag.js: als A. Na het versturen, vóór de melding: als C. | | | |

Nog niet gebouwd: automatisch herstel van ontbrekende aankopen vanaf de server (GA4 Measurement Protocol).
Dat vraagt een API-secret uit GA4-beheer en het bewaren van de GA4-client-ID per order. Dat is een aparte
beslissing van de eigenaar, ook voor privacy.

### Controle tegen GA4

1. In GA4: Verkennen → Vrije vorm, rij-dimensie **Transactie-ID**, statistiek **E-commerce-aankopen**
   (Ecommerce purchases). Geen andere dimensies of filters toevoegen, en zorg dat alle rijen in de export zitten
   (niet alleen de eerste pagina). Kies een afgesloten periode, liefst ≥ 2 dagen geleden (verwerkingstijd), en
   exporteer als CSV of TSV.
2. Proef: `php artisan analytics:import-ga4 export.csv --from=2026-11-10 --to=2026-11-16 --dry-run`
3. Echt: hetzelfde zonder `--dry-run`. Elke betaalde order (geen test) met orderdatum in `--from`…`--to`
   krijgt het aantal uit de export, of 0.
4. `php artisan orders:report --from=… --to=… --missing` toont welke orders ontbreken of nooit afgehandeld zijn.

De import weigert, vóór er iets wordt opgeslagen:

- een export zonder **E-commerce-aankopen**. Een export met alleen Transactie-ID (of met opbrengst) heeft één
  regel per transactie-ID, ook als de aankoop dubbel in GA4 staat; daaruit is het aantal niet af te leiden;
- algemene tellingen als **Aantal gebeurtenissen** (Event count) of **Transacties**: die bewijzen niet dat
  het om aankoopevents gaat;
- een aantal dat geen geheel getal is (`1.5`, leeg, tekst, `1,234`). De melding noemt de regel.

Regels met `#` (GA4-kop), `(not set)` en totaalregels worden overgeslagen. Staat een transactie-ID op meer
regels (bijvoorbeeld meerdere tabellen in één bestand), dan worden de aantallen opgeteld.

**Periode.** `--from`/`--to` gaan op orderdatum (Europe/Amsterdam); GA4 telt op de datum van het event. Een order
net voor middernacht die pas na middernacht wordt betaald, of een klant die de bevestigingspagina later opent,
staat in GA4 op een latere dag. Neem de export daarom een paar dagen ruimer dan `--from`/`--to`, nooit krapper:
ID's buiten de orderperiode worden alleen genoemd, niet opgeslagen. Een te krappe of gefilterde export geeft
onterecht "ontbreekt". De importer is nog niet getoetst aan een echte export van deze property: doe de eerste
vergelijking met `--dry-run`.

Overige grenzen:

- Opent de klant de bevestigingspagina nooit (alleen de webhook komt binnen), dan is er geen `purchase` in GA4.
- Keert de klant uit de bank-app terug in een andere browser, dan is dat voor GA4 een andere gebruiker: de
  aankoop telt, maar valt buiten de trechter van zijn eerdere stappen.
- Een terugbetaling stuurt geen `refund`-event. Terugbetalingen staan als creditnota in het bestelbeheer en in
  `orders:report`. Dat is de bron.

## Uitrol en terugval

- **Volgorde:** de code werkt ook zonder de nieuwe kolommen (`PurchaseTracking::ready()`): betaling, webhook en
  bevestigingspagina gaan gewoon door, alleen zonder `purchase`-event, en `orders:report` weigert met een melding.
  Er is dus geen moment waarop de site stukgaat tussen de deploy en `php artisan migrate --force`.
  Een order die in dat tussenmoment betaald wordt, krijgt bij de migratie `legacy` en wordt niet gemeten
  (ondermeting, geen dubbeltelling). Draai `migrate` daarom direct na de deploy.
- **Migratie:** voegt alleen kolommen toe en zet `analytics_purchase_legacy` voor bestaande betaalde orders.
- **Terugval:** zet alleen de code terug, draai `migrate:rollback` **niet**. De oude code negeert de extra kolommen;
  zo blijft de meetstatus bewaard voor een nieuwe poging.

## Testverkeer

- Buiten productie (`APP_ENV` ≠ `production`) gaan alle events met `debug_mode` naar GA4. Zet lokaal
  `GA4_MEASUREMENT_ID=` (leeg) om helemaal niets te versturen.
- Een betaling die Mollie als `test` meldt, krijgt `payment_mode = test`. De bevestigingspagina stuurt dan alles
  als debug-event, en `orders:report` telt hem niet mee.
- In GA4 kan het datafilter **Ontwikkelaarsverkeer** debug-events uit de rapporten sluiten. Stel eerst vast hoe
  het nu staat (*Testen* of *Actief*) en wat het moet uitsluiten, vóór iemand het wijzigt.

## Rapport in GA4 (handmatig in te richten)

Verkennen → Trechterverkenning, *open trechter: nee*, stappen:
`session_start` → `pdf_upload` → `add_shipping_info` → `add_payment_info` → `purchase`.
`begin_checkout` hoort er níet tussen (zie Toegestane volgorden); gebruik hem in een aparte verkenning.
Uitsplitsen naar `shipping_tier` voor bezorgen/afhalen. Voor de fouten een vrije verkenning op `pdf_upload_failed`
en `payment_start_failed` met `error_type`. Registreer daarvoor de aangepaste dimensies `error_type`,
`shipping_tier` (standaard), `checkout_stage` en `delivery_type` (Beheer → Aangepaste definities). Aangepaste dimensies werken pas voor
gegevens vanaf het moment van registratie.

Vergelijk de periode vóór de uitrol niet één-op-één met de periode erna: de definities zijn veranderd.
