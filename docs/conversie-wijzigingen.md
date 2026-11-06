# Conversiewijzigingen (hypotheses)

Per wijziging: datum, hypothese, pagina, wat je meet en hoe je terugdraait. Bij het huidige volume
(ongeveer 3–12 betaalde orders per week) zeggen één of twee orders niets. Beoordeel dit over meerdere weken
en met `orders:report`, niet met een percentage uit GA4.

| Datum | Pagina | Wijziging | Hypothese | Meten | Terugdraaien |
|-------|--------|-----------|-----------|-------|--------------|
| 2026-11-06 (voorbereid, niet live) | `/boekje-maken` | Toepassingen (cadeau, recepten, reis) met illustratie en uploadknop in de hero | Wie de eigen toepassing herkent, uploadt vaker | `landing_cta_click` per `cta_location`; `pdf_upload` met landingspagina `/boekje-maken` | `usecases` weghalen in `LandingPageController::boekje()` |
| 2026-11-06 (voorbereid) | `/boekje-maken` | Totaalprijsvoorbeeld (printen, nieten, start, verzending; afhalen apart) | Een totaalprijs vooraf voorkomt een verrassing door startkosten en verzending | Verhouding `pdf_upload` → `add_payment_info` vanaf deze landingspagina | `priceExample` weghalen |
| 2026-11-06 (voorbereid) | `/boekje-maken`, `/cursusmateriaal-printen` | Vergelijking per aantal exemplaren (zelfde PDF en afwerking), niets vooraf gekozen | Zichtbare prijs per exemplaar maakt een extra exemplaar logischer en kan de orderwaarde verhogen | Gemiddelde orderwaarde en `quantity` in `orders:report --details` | `quantities` weghalen |
| 2026-11-06 (voorbereid) | `/boekje-maken`, `/cursusmateriaal-printen` | Uitleg geniet boekje vs. losse prints, enkel- vs. dubbelzijdig | Minder twijfel over de juiste afwerking | `select_finishing`-wijzigingen na upload | `choice` weghalen |
| 2026-11-06 (voorbereid) | `/pdf-printen-met-spoed`, `/printen-delft` | Spoedvoorwaarden direct onder de hero (besteltijd, aantal, afhalen, bezorgen) | Wie spoed zoekt, beslist sneller als de voorwaarden meteen zichtbaar zijn | Aandeel `shipping_tier = pickup` en betaalstart vanaf deze pagina's | `conditions` weghalen |
| 2026-11-06 (voorbereid) | homepage | Anker `#upload` bestond niet; knoppen van alle landingspagina's kwamen bovenaan de homepage uit | Technische fout; geen hypothese | – | `<span id="upload">` weghalen |

Alle blokken staan in `resources/views/landing/partials/koophulp.blade.php` en verschijnen alleen als de pagina de
gegevens meegeeft. Prijzen komen uit `Order::calculatePrice`; een tariefwijziging in `config/pricing.php` werkt
direct door (bewaakt door `LandingKoophulpTest`).
