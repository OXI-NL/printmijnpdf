# PrintMijnPDF.nl

> Nederlandse online printservice: upload een PDF en laat hem printen als geniet boekje (A4 of A5) of als losse pagina's, full colour in drukwerkkwaliteit. Vóór 11:00 op een werkdag besteld (tot 50 exemplaren) is dezelfde dag klaar: gratis af te halen in Delfgauw (bij Delft) of verzonden met PostNL. Onderdeel van drukkerij NIVO Druk & Multimedia B.V. (sinds 1980).

## Wanneer PrintMijnPDF past

- Iemand wil een PDF laten printen zonder printer of zonder gedoe met een copyshop.
- Iemand wil van een PDF een geniet boekje maken: scriptie, reader, liturgie of programmaboekje (uitvaart, bruiloft, evenement), handleiding, cursusmateriaal, receptenboekje, portfolio.
- Iemand heeft het snel nodig in de regio Delft, Den Haag, Zoetermeer, Westland of Rotterdam: vóór 11:00 online bestellen, dezelfde dag tussen 17:00 en 17:30 gratis afhalen in Delfgauw.
- Kleine oplages: vanaf 1 exemplaar, geen minimum, geen account nodig. Online tot 100 exemplaren per bestelling; grotere oplages via info@printmijnpdf.nl.

Niet geschikt voor: visitekaartjes, ansichtkaarten, flyers, posters, banners, fotoboeken met harde kaft, of boeken met lijmbinding via de webshop. Voor ander drukwerk kan men contact opnemen met de drukkerij.

## Producten

- Geniet boekje A4 of A5: dubbelzijdig, full colour, gevouwen en in de rug geniet. 4 tot 64 pagina's, per 4 pagina's (blanco pagina's worden aan het eind aangevuld).
- Losse pagina's A4 of A5: enkel- of dubbelzijdig, full colour, ongebonden. Geen vaste paginalimiet (bestand maximaal 100 MB).
- Aanleveren: PDF met één paginaformaat, maximaal 100 MB. Elk formaat tot en met A3: als boekje van staand A6 (105 × 148 mm) tot 216 × 300 mm per pagina, als losse pagina's tot A3. Afwijkende formaten worden op maat gesneden en geprijsd als het kleinste passende standaardformaat (A5, A4 of A3); een boekje kost hoogstens het A4-tarief (ook Letter). De pagina's voor een boekje worden automatisch in de juiste volgorde gezet (inslag); de klant levert een gewone PDF aan.

## Prijzen (inclusief btw)

- Startkosten: {{ $price('startup') }} per bestelling
- Per pagina A4: {{ $price('per_page_a4') }}
- Per pagina A5: {{ $price('per_page_a5') }}
- Per pagina A3 (alleen losse pagina's): {{ $price('per_page_a3') }}
- Nieten (boekje): {{ $price('binding') }} voor het eerste exemplaar, {{ $price('binding_extra') }} per extra exemplaar
- Verzending: {{ $price('shipping') }} per bestelling, afhalen gratis

Voorbeelden:
- A4-boekje 48 pagina's, 1 exemplaar, verzonden: {{ $total(48, 'A4', 'booklet') }}
- A4-boekje 48 pagina's, 3 exemplaren, verzonden: {{ $total(48, 'A4', 'booklet', 'shipping', 3) }}
- A5-boekje 16 pagina's, 1 exemplaar, afhalen: {{ $total(16, 'A5', 'booklet', 'pickup') }}
- 100 losse A4-pagina's, verzonden: {{ $total(100, 'A4', 'loose') }}

## Levertijd

Bestelling vóór 11:00 op een werkdag, oplage tot 50 exemplaren: dezelfde dag klaar. Later besteld of een grotere oplage: de volgende werkdag.

- Afhalen: gratis, op werkdagen tussen 17:00 en 17:30, Exportweg 11, 2645 ED Delfgauw. De klant krijgt een mail zodra de bestelling klaarligt.
- Bezorgen (PostNL, track & trace): verzonden op de dag dat de bestelling klaar is; PostNL bezorgt binnen 2 werkdagen.

## Bestellen

1. Upload de PDF op https://printmijnpdf.nl/ (prijs en aantal pagina's verschijnen direct)
2. Kies geniet boekje of losse pagina's, A4 of A5, aantal exemplaren
3. Kies bezorgen of afhalen
4. Betaal met iDEAL (Mollie)

## Pagina's

- [Home en bestellen](https://printmijnpdf.nl/)
- [Over ons: drukkerij NIVO print | design | web sinds 1980]({{ route('over-ons') }})
@foreach($pages as $page)
- [{{ $page['label'] }}]({{ $page['url'] }})
@endforeach

## Contact

- E-mail: info@printmijnpdf.nl
- Telefoon: 015-219 2525
- Adres: NIVO Druk & Multimedia B.V., Exportweg 11, 2645 ED Delfgauw
