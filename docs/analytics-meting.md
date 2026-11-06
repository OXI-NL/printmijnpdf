# Meting PrintMijnPDF (GA4)

Bijgewerkt: 6 november 2026. Meet-ID `G-9F48GD4CX5` (config `analytics.ga4_measurement_id`, env `GA4_MEASUREMENT_ID`).

**De orderadministratie is de bron voor omzet.** GA4 is ondersteunend (kanaal, trechter).
Financiële controletelling: `php artisan orders:report --from=2026-11-06 --to=2026-11-12 [--details]`
(unieke betaalde orders op orderdatum, bruto incl. btw, terugbetalingen op creditnotadatum, testbetalingen uitgesloten).

## Trechter

| # | Fase | Event | Trigger (precies) | Telt opnieuw? |
|---|------|-------|-------------------|---------------|
| 1 | Bezoek | `page_view` / `session_start` | GA4 automatisch | – |
| 2 | PDF geanalyseerd, prijs in beeld | `pdf_upload` + `view_item` | `/api/calculate-price` gaf `success`; afwerking en prijs verschijnen direct | Eén keer per geanalyseerde PDF |
| 3 | Verzenden of afhalen | `add_shipping_info` (`shipping_tier`: `delivery`/`pickup`) | Klik op de bezorgoptie | Eerste keuze en alleen echte wijzigingen per PDF |
| 4 | Gegevens invullen | `begin_checkout` | Eerste invoer in een veld van de klantgegevens | Eén keer per PDF |
| 5 | Betaalstart | `add_payment_info` (`payment_type: ideal`) | Server heeft de order gemaakt en de klant gaat naar Mollie | Eén keer per poging |
| 6 | Betaalde order | `purchase` | Bevestigingspagina van een betaalde order die de aankoop bij de server claimt | Eén keer per ordernummer (zie onder) |

Prijs en afwerking staan meteen na de analyse in beeld; daarom vallen fase 2 en "afwerking/prijs" samen.
`view_item.value` is de prijs met de standaardafwerking, zonder bezorgkosten.

Bezorgen en afhalen zijn allebei fase 3. Wie van keuze wisselt, krijgt een tweede `add_shipping_info`.
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

Er gaan nooit bestandsnamen, PDF-inhoud, namen, e-mailadressen of adressen naar GA4.

Vervallen: `cart_abandonment` (ging ook af bij de doorverwijzing naar Mollie) en `select_item` (dubbel met `select_finishing`).

## Aankoop: wat de oplossing waarborgt

`App\Services\PurchaseTracking`, kolommen `analytics_purchase_claimed_at` en `analytics_purchase_sent_at`.

1. De bevestigingspagina krijgt het `purchase`-event alleen als de server de aankoop atomair claimt:
   de order is betaald, de aankoop is nog niet bevestigd en er loopt geen claim van minder dan 10 minuten.
2. Zodra gtag het event heeft afgehandeld (`event_callback`), meldt de pagina dat aan
   `POST /bestelling/{nr}/aankoop-gemeten` met een HMAC-token. Vanaf dan krijgt geen enkele pagina, browser of apparaat
   dit ordernummer nog als aankoop.
3. Komt die melding niet (gtag.js geblokkeerd, netwerkfout, tab dicht), dan verloopt de claim na 10 minuten en
   probeert een volgend bezoek het opnieuw. Er staat dus geen permanente vlag vóór het versturen.
4. Bij de uitrol zijn alle bestaande betaalde orders als gemeten gemarkeerd. Zo telt een oude bevestigingspagina niet opnieuw.

Grenzen die blijven bestaan:

- Opent de klant de bevestigingspagina nooit (alleen de webhook komt binnen), dan is er geen `purchase` in GA4.
  De order staat wel in `orders:report` met "GA4 bevestigd: nee".
- gtag roept `event_callback` ook aan na een time-out van 2 s, ook als een adblocker alleen het verzoek naar
  Google blokkeert. Zo'n aankoop telt als gemeten maar ontbreekt in GA4: ondermeting, geen dubbeltelling.
- Twee bezoeken meer dan 10 minuten na elkaar, terwijl het eerste het event wél verstuurde maar de bevestiging
  nooit aankwam, kunnen alsnog dubbel tellen. Dat vereist een netwerkfout precies tussen verzenden en bevestigen.
- Een terugbetaling stuurt geen `refund`-event. Terugbetalingen staan als creditnota in het bestelbeheer en in
  `orders:report`. Dat is de bron.

## Testverkeer

- Buiten productie (`APP_ENV` ≠ `production`) gaan alle events met `debug_mode` naar GA4. Zet lokaal
  `GA4_MEASUREMENT_ID=` (leeg) om helemaal niets te versturen.
- Een betaling die Mollie als `test` meldt, krijgt `payment_mode = test`. De bevestigingspagina stuurt dan alles
  als debug-event, en `orders:report` telt hem niet mee.
- In GA4 sluit het datafilter **Ontwikkelaarsverkeer** debug-events uit de rapporten. Daarvoor moet dat filter
  op *Actief* staan (Beheer → Gegevensverzameling → Gegevensfilters).

## Rapport in GA4 (handmatig in te richten)

Verkennen → Trechterverkenning, *open trechter: nee*, stappen:
`session_start` → `pdf_upload` → `add_shipping_info` → `begin_checkout` → `add_payment_info` → `purchase`.
Uitsplitsen naar `shipping_tier` voor bezorgen/afhalen. Voor de fouten een vrije verkenning op `pdf_upload_failed`
en `payment_start_failed` met `error_type`. Registreer daarvoor de aangepaste dimensies `error_type`,
`shipping_tier` (standaard), `checkout_stage` en `delivery_type` (Beheer → Aangepaste definities). Aangepaste dimensies werken pas voor
gegevens vanaf het moment van registratie.

Vergelijk de periode vóór de uitrol niet één-op-één met de periode erna: de definities zijn veranderd.
