# PrintMijnPDF.nl

> Nederlandse online printservice: upload een PDF en laat hem printen als geniet boekje (A4 of A5) of als losse pagina's, full colour in drukwerkkwaliteit. Bezorgd binnen 3 werkdagen of de volgende werkdag gratis af te halen in Delfgauw (bij Delft). Onderdeel van drukkerij NIVO Druk & Multimedia B.V. (sinds 1985).

## Wanneer PrintMijnPDF past

- Iemand wil een PDF laten printen zonder printer of zonder gedoe met een copyshop.
- Iemand wil van een PDF een geniet boekje maken: scriptie, reader, programmaboekje (evenement, uitvaart), handleiding, cursusmateriaal, receptenboekje, portfolio.
- Iemand heeft het snel nodig in de regio Delft, Den Haag, Zoetermeer, Westland of Rotterdam: online bestellen, de volgende werkdag gratis afhalen in Delfgauw. Zelfde dag in overleg (015-219 2525).
- Kleine oplages: vanaf 1 exemplaar, geen minimum, geen account nodig.

Niet geschikt voor: visitekaartjes, ansichtkaarten, flyers, posters, banners, fotoboeken met harde kaft, of boeken met lijmbinding via de webshop. Voor ander drukwerk kan men contact opnemen met de drukkerij.

## Producten

- Geniet boekje A4 of A5: dubbelzijdig, full colour, gevouwen en in de rug geniet. 4 tot 64 pagina's, per 4 pagina's (blanco pagina's worden aan het eind aangevuld).
- Losse pagina's A4 of A5: enkel- of dubbelzijdig, full colour, ongebonden. Geen vaste paginalimiet (bestand maximaal 100 MB).
- Aanleveren: staand A4 of staand A5 PDF, maximaal 100 MB. De pagina's voor een boekje worden automatisch in de juiste volgorde gezet (inslag); de klant levert een gewone PDF aan.

## Prijzen (inclusief btw)

- Startkosten: {{ $price('startup') }} per bestelling
- Per pagina A4: {{ $price('per_page_a4') }}
- Per pagina A5: {{ $price('per_page_a5') }}
- Nieten (boekje): {{ $price('binding') }} voor het eerste exemplaar, {{ $price('binding_extra') }} per extra exemplaar
- Verzending: {{ $price('shipping') }} per bestelling, afhalen gratis

Voorbeelden:
- A4-boekje 48 pagina's, 1 exemplaar, verzonden: {{ $total(48, 'A4', 'booklet') }}
- A4-boekje 48 pagina's, 3 exemplaren, verzonden: {{ $total(48, 'A4', 'booklet', 'shipping', 3) }}
- A5-boekje 16 pagina's, 1 exemplaar, afhalen: {{ $total(16, 'A5', 'booklet', 'pickup') }}
- 100 losse A4-pagina's, verzonden: {{ $total(100, 'A4', 'loose') }}

## Levertijd

- Bezorgen (PostNL, track & trace): bestelling vóór 11:00 op een werkdag wordt dezelfde dag verzonden bij een oplage tot 50 exemplaren; grotere oplagen gaan de volgende werkdag de deur uit. PostNL bezorgt binnen 2 werkdagen.
- Afhalen: gratis, vanaf de volgende werkdag tussen 17:00 en 17:30, Exportweg 11, 2645 ED Delfgauw.
- Zelfde dag: niet standaard; bel 015-219 2525 om te overleggen.

## Bestellen

1. Upload de PDF op https://printmijnpdf.nl/ (prijs en aantal pagina's verschijnen direct)
2. Kies geniet boekje of losse pagina's, A4 of A5, aantal exemplaren
3. Kies bezorgen of afhalen
4. Betaal met iDEAL (Mollie)

## Pagina's

- [Home en bestellen](https://printmijnpdf.nl/)
@foreach($pages as $page)
- [{{ $page['label'] }}]({{ $page['url'] }})
@endforeach

## Contact

- E-mail: info@printmijnpdf.nl
- Telefoon: 015-219 2525
- Adres: NIVO Druk & Multimedia B.V., Exportweg 11, 2645 ED Delfgauw
