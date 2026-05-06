# 🔒 Wekelijkse Security & Dependency Check — printmijnpdf.nl

**Stack:** Laravel, PDF-naar-print webservice, Mollie/iDEAL betalingen, GA4 e-commerce tracking, Resend voor mail
**Hosting:** Ploi.io
**Repo:** GitHub (deploy via Ploi)

Voer een complete security- en dependency-audit uit. Werk de stappen één voor één af en rapporteer telkens duidelijk wat er gevonden is en wat er gedaan is.

## 1. Identificeer de huidige staat
- Toon Laravel-versie, PHP-versie en Node-versie
- Toon de inhoud van `composer.json` en `package.json` (alleen dependencies-secties)
- Bevestig dat de app lokaal opstart

## 2. Check op kwetsbaarheden (CVE's)
- `composer audit`
- `npm audit`

Geef per kwetsbaarheid:
- Severity, CVE-nummer, package + versie → veilige versie
- Specifieke impact op printmijnpdf (let extra op **payment-libs**, **PDF-verwerking**, **upload-handling** — dit zijn de hoog-risico flows omdat klanten betalen en bestanden uploaden)

## 3. Check op verouderde dependencies
- `composer outdated --direct`
- `npm outdated`

**Extra aandacht voor:**
- `mollie/mollie-api-php` — payment integratie
- PDF-libraries (`pikepdf` als je die via shell aanroept, of PHP-equivalenten)
- `resend/resend-php` of `resend/resend-laravel`
- File upload / validation packages
- `phpseclib/phpseclib` (recent High CVE gehad)
- Image-processing libs (`intervention/image`)

## 4. SEO & analytics check (geen security maar wel wekelijks)
- Controleer of GA4 e-commerce events nog binnenkomen (test één order in test-modus)
- Check Google Search Console: nog steeds 7 pagina's geïndexeerd, geen nieuwe crawl errors?
- Snelle check op nieuwe 404's of broken links

## 5. Actieplan
Geprioriteerde lijst:
- 🔴 **Direct fixen:** High/Critical CVE's, vooral in betaal- of upload-paden
- 🟡 **Deze week:** Low/Medium CVE's + patch updates
- 🟢 **Plannen:** Minor/major met breaking changes
- ⚪ **Skip:** met reden

## 6. Voer fixes uit
Per fix:
- Update de package(s)
- Run `php artisan test`
- Test handmatig de **kritieke flows**:
  - PDF uploaden en preview
  - Mollie/iDEAL betaalflow (test-modus)
  - Order-bevestiging via Resend
  - GA4 event afvuren bij purchase
- Commit: `chore(security): bump X from Y to Z (CVE-XXXX-XXXX)`

## 7. Deploy & verifieer
- Push naar GitHub
- Deploy via Ploi
- Test in productie: doorloop één test-order end-to-end
- Geef commit hash(es) en deploy timestamp

## 8. Rapportage voor Kay
```
✅ printmijnpdf.nl — Wekelijkse security check [datum]

Gevonden: X kwetsbaarheden (Y High, Z Low)
Opgelost: [lijst]
Outdated geüpdatet: [lijst]
Skipped: [lijst met reden]
Deploy: commit [hash] op [tijd]
Kritieke flows getest: ✓ upload, ✓ Mollie, ✓ Resend, ✓ GA4
```

## Belangrijk
- Stop niet bij de eerste fout — ga door en rapporteer aan het eind
- **Betaalflow faalt na update? Onmiddellijk rollback** — geen risico met klantenbetalingen
- Twijfel over major update → vraag het mij eerst
