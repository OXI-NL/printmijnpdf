<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Alle landingspagina's: slug => [routenaam, linktekst]. Bron voor de
     * sitemap, de interne links op de homepage en het blok "Meer printen".
     */
    public const PAGES = [
        'pdf-laten-printen' => ['landing.pdf', 'PDF laten printen'],
        'pdf-naar-boekje' => ['landing.pdf-naar-boekje', 'PDF naar boekje'],
        'boekje-printen' => ['landing.boekje-printen', 'Boekje printen (A4 en A5)'],
        'pdf-printen-met-spoed' => ['landing.spoed', 'PDF printen met spoed'],
        'prijzen' => ['landing.prijzen', 'Prijzen en rekenvoorbeelden'],
        'pdf-aanleveren' => ['landing.aanleveren', 'PDF aanleveren: afloop en marges'],
        'scriptie-printen' => ['landing.scriptie', 'Scriptie printen'],
        'reader-printen' => ['landing.reader', 'Reader printen'],
        'cursusmateriaal-printen' => ['landing.cursusmateriaal', 'Cursusmateriaal printen'],
        'handleiding-printen' => ['landing.handleiding', 'Handleiding printen'],
        'boekje-maken' => ['landing.boekje', 'Eigen boekje maken'],
        'zakelijk' => ['landing.zakelijk', 'Zakelijk printen'],
        'liturgie-uitvaart-printen' => ['landing.uitvaart', 'Liturgie uitvaart printen'],
        'boekje-bruiloft-printen' => ['landing.bruiloft', 'Boekje bruiloft printen'],
        'printen-zonder-printer' => ['landing.zonder-printer', 'Printen zonder printer'],
        'printen-delft' => ['landing.delft', 'Printen in Delft en omgeving'],
    ];

    /**
     * @return array<int, array{url: string, label: string}>
     */
    public static function links(?string $except = null): array
    {
        $links = [];
        foreach (self::PAGES as $slug => [$route, $label]) {
            if ($slug !== $except) {
                $links[] = ['url' => route($route), 'label' => $label];
            }
        }

        return $links;
    }

    /**
     * Render een landingspagina met de gedeelde blokken (prijzen, links)
     */
    protected function page(array $data): View
    {
        $examples = [
            ['label' => "A5-boekje, 16 pagina's", 'pages' => 16, 'format' => 'A5', 'binding' => 'booklet'],
            ['label' => "A4-boekje, 24 pagina's", 'pages' => 24, 'format' => 'A4', 'binding' => 'booklet'],
            ['label' => "A4-boekje, 48 pagina's", 'pages' => 48, 'format' => 'A4', 'binding' => 'booklet'],
            ['label' => "A4, 100 losse pagina's", 'pages' => 100, 'format' => 'A4', 'binding' => 'loose'],
        ];

        $data['priceExamples'] = array_map(fn ($e) => [
            'label' => $e['label'],
            'binding' => $e['binding'] === 'booklet' ? 'Geniet boekje' : 'Losse pagina\'s',
            'total' => self::euro(Order::calculatePrice($e['pages'], $e['format'], $e['binding'], 'shipping', 1)['total']),
        ], $examples);
        $data['shippingPrice'] = self::euro((int) config('pricing.shipping', 750));
        $data['related'] = self::links($data['slug'] ?? null);

        return view('landing.page', $data);
    }

    private static function euro(int $cents): string
    {
        return '€ ' . number_format($cents / 100, 2, ',', '.');
    }

    /**
     * Scriptie printen - voor studenten HBO/WO
     */
    public function scriptie(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Scriptie Printen als Boekje | Binnen 1 Werkdag Verzonden | PrintMijnPDF',
                'description' => 'Laat je scriptie printen als geniet boekje (tot 64 pagina\'s) of als losse pagina\'s. Full colour drukwerkkwaliteit, binnen 1 werkdag verzonden. Voor HBO en WO.',
                'canonical' => route('landing.scriptie'),
                'keywords' => 'scriptie printen, scriptie inbinden, afstudeerscriptie drukken, scriptie laten printen',
            ],
            'hero' => [
                'title' => 'Scriptie printen als boekje',
                'subtitle' => 'Jouw afstudeerwerk verdient drukwerkkwaliteit',
                'cta' => 'Upload je scriptie',
            ],
            'benefits' => [
                [
                    'icon' => 'clock',
                    'title' => 'Binnen 1 werkdag verzonden',
                    'text' => 'Binnen 1 werkdag op de post met PostNL, daarna bezorgt PostNL binnen 2 werkdagen. Of gratis afhalen.',
                ],
                [
                    'icon' => 'palette',
                    'title' => 'Full colour drukwerk',
                    'text' => 'Alle grafieken, tabellen en afbeeldingen in volle kleur.',
                ],
                [
                    'icon' => 'book',
                    'title' => 'Professioneel geniet',
                    'text' => 'Gevouwen en in de rug geniet, tot 64 pagina\'s. Langer? Dan printen we losse pagina\'s.',
                ],
                [
                    'icon' => 'euro',
                    'title' => 'Scherpe prijs',
                    'text' => 'Vanaf €0,15 per pagina. Geen verborgen kosten.',
                ],
            ],
            'content' => [
                'intro' => 'Je hebt maanden aan je scriptie gewerkt. Nu is het tijd om er iets moois van te maken. Bij PrintMijnPDF print je je scriptie in echte drukwerkkwaliteit — geen verschoten kleuren of goedkoop kopieerpapier, maar professioneel resultaat waar je trots op kunt zijn.',

                'sections' => [
                    [
                        'title' => 'Waarom je scriptie bij ons printen?',
                        'text' => 'Wij zijn geen online print-app, maar een echte drukkerij. NIVO print | design | web bestaat al sinds 1980 en print dagelijks voor bedrijven, overheden en onderwijsinstellingen. Die kwaliteit krijg jij nu ook voor je scriptie — tegen een studentvriendelijke prijs.',
                    ],
                    [
                        'title' => 'Hoe werkt het?',
                        'text' => 'Upload je PDF, kies voor een geniet boekje, vul je gegevens in en reken af met iDEAL. Binnen 1 werkdag versturen we je scripties met PostNL. Heb je haast? Bel ons, dan kijken we wat mogelijk is.',
                    ],
                    [
                        'title' => 'Meerdere exemplaren nodig?',
                        'text' => 'De meeste studenten bestellen 3-5 exemplaren: voor zichzelf, ouders, en de beoordelend docent. Hoe meer je bestelt, hoe voordeliger per stuk.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Hoeveel pagina\'s mag mijn scriptie hebben?',
                    'answer' => 'Voor een geniet boekje geldt een maximum van 64 pagina\'s. Is je scriptie langer, dan kun je hem online als losse pagina\'s bestellen. Wil je hem gebonden hebben, bijvoorbeeld met een ringband of lijmbinding? Dat kan niet via de website, maar wel via de drukkerij: mail naar info@printmijnpdf.nl met je naam en telefoonnummer, dan bellen we je.',
                ],
                [
                    'question' => 'Welk formaat moet mijn PDF zijn?',
                    'answer' => 'De meeste scripties zijn A4, maar we printen elk formaat tot en met A3. Zorg dat je PDF in het formaat is opgeslagen waarin je hem gedrukt wilt hebben.',
                ],
                [
                    'question' => 'Kan ik een proefdruk bestellen?',
                    'answer' => 'Ja, bestel gewoon 1 exemplaar om te controleren of alles goed is. Ben je tevreden? Bestel dan de rest.',
                ],
                [
                    'question' => 'Wordt dubbelzijdig geprint?',
                    'answer' => 'Ja, boekjes worden standaard dubbelzijdig geprint. Zo bespaar je papier en krijg je een compacter resultaat.',
                ],
                [
                    'question' => 'Kan ik mijn scriptie ook laten inbinden met een harde kaft?',
                    'answer' => 'Online bieden we een geniet boekje (tot 64 pagina\'s) en losse pagina\'s. Een harde kaft, lijmbinding of ringband regelen we via de drukkerij: mail naar info@printmijnpdf.nl, dan bespreken we de mogelijkheden en de prijs.',
                ],
            ],
            'slug' => 'scriptie-printen',
        ]);
    }

    /**
     * Reader printen - voor studenten
     */
    public function reader(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Reader Printen | Studiehandleiding Drukken | PrintMijnPDF',
                'description' => 'Laat je reader of studiehandleiding printen in full colour. Binnen 1 werkdag verzonden, vanaf €0,15 per pagina. Echte drukwerkkwaliteit.',
                'canonical' => route('landing.reader'),
                'keywords' => 'reader printen, studiehandleiding drukken, syllabus printen, collegebundel',
            ],
            'hero' => [
                'title' => 'Reader printen',
                'subtitle' => 'Je studiemateriaal professioneel geprint',
                'cta' => 'Upload je reader',
            ],
            'benefits' => [
                [
                    'icon' => 'clock',
                    'title' => 'Binnen 1 werkdag verzonden',
                    'text' => 'Snel in huis, ook als het semester al begonnen is.',
                ],
                [
                    'icon' => 'palette',
                    'title' => 'Full colour',
                    'text' => 'Schema\'s en diagrammen in kleur, makkelijker studeren.',
                ],
                [
                    'icon' => 'book',
                    'title' => 'Handzaam formaat',
                    'text' => 'A4 of A5, als boekje om mee te nemen naar college.',
                ],
                [
                    'icon' => 'users',
                    'title' => 'Bestel samen',
                    'text' => 'Combineer met studiegenoten voor extra voordeel.',
                ],
            ],
            'content' => [
                'intro' => 'Een digitale reader is handig, maar soms wil je gewoon papier. Markeren, aantekeningen maken, doorbladeren zonder scherm. Bij PrintMijnPDF laat je je reader drukken in professionele kwaliteit.',

                'sections' => [
                    [
                        'title' => 'Beter studeren met papier',
                        'text' => 'Onderzoek toont aan dat lezen op papier leidt tot betere kennisretentie. Print je reader en haal meer uit je studietijd.',
                    ],
                    [
                        'title' => 'Samen bestellen',
                        'text' => 'Spreek af met je studiegroep en bestel meerdere exemplaren. Scheelt verzendkosten en je hebt allemaal hetzelfde materiaal.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Kan ik een reader van 100+ pagina\'s printen?',
                    'answer' => 'Boekjes gaan tot 64 pagina\'s. Langere readers kunnen als losse pagina\'s of met ringband. Neem contact op voor de mogelijkheden.',
                ],
                [
                    'question' => 'Mijn reader is een beveiligd PDF, kan dat?',
                    'answer' => 'Als je de PDF kunt openen en bekijken, kunnen wij hem printen. Print-beveiliging kan soms problemen geven — neem in dat geval contact op.',
                ],
            ],
            'slug' => 'reader-printen',
        ]);
    }

    /**
     * Cursusmateriaal - voor trainers/docenten
     */
    public function cursusmateriaal(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Cursusmateriaal Printen | Trainingsmateriaal Drukken | PrintMijnPDF',
                'description' => 'Print je cursusmateriaal of trainingshandleiding professioneel. Full colour, binnen 1 werkdag verzonden. Ideaal voor trainers, coaches en docenten.',
                'canonical' => route('landing.cursusmateriaal'),
                'keywords' => 'cursusmateriaal printen, trainingsmateriaal drukken, syllabus laten printen, workshop materiaal',
            ],
            'hero' => [
                'title' => 'Cursusmateriaal printen',
                'subtitle' => 'Professioneel materiaal voor je training of workshop',
                'cta' => 'Upload je materiaal',
            ],
            'benefits' => [
                [
                    'icon' => 'briefcase',
                    'title' => 'Professionele uitstraling',
                    'text' => 'Maak indruk op deelnemers met drukwerk van hoge kwaliteit.',
                ],
                [
                    'icon' => 'refresh',
                    'title' => 'Flexibele oplages',
                    'text' => 'Van 1 tot 100 exemplaren, bestel wat je nodig hebt.',
                ],
                [
                    'icon' => 'clock',
                    'title' => 'Snel geleverd',
                    'text' => 'Binnen 1 werkdag verzonden, ook voor last-minute trainingen.',
                ],
                [
                    'icon' => 'shield',
                    'title' => 'Betrouwbare partner',
                    'text' => 'Drukkerij met 35+ jaar ervaring. We leveren wat we beloven.',
                ],
            ],
            'content' => [
                'intro' => 'Als trainer of coach weet je: goed materiaal maakt het verschil. Deelnemers die iets in handen hebben, onthouden meer en nemen je serieuzer. PrintMijnPDF levert cursusmateriaal in echte drukwerkkwaliteit.',

                'sections' => [
                    [
                        'title' => 'Voor trainers en coaches',
                        'text' => 'Of je nu een eenmalige workshop geeft of een terugkerende training — wij printen je syllabi, werkboeken en hand-outs. Kleine oplages, geen minimum.',
                    ],
                    [
                        'title' => 'Zakelijke factuur nodig?',
                        'text' => 'Geen probleem. Neem contact op voor een factuur op bedrijfsnaam met BTW-specificatie.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Kan ik een factuur krijgen?',
                    'answer' => 'Ja, neem contact op via info@printmijnpdf.nl met je bedrijfsgegevens, dan sturen we een factuur.',
                ],
                [
                    'question' => 'Zijn er kortingen bij grotere aantallen?',
                    'answer' => 'Neem contact op voor een offerte als je regelmatig of in grote aantallen bestelt.',
                ],
            ],
            'slug' => 'cursusmateriaal-printen',
        ]);
    }

    /**
     * Boekje maken - algemeen/particulier
     */
    public function boekje(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Eigen Boekje Maken | Receptenboek, Fotoboekje en Meer | PrintMijnPDF',
                'description' => 'Maak je eigen boekje van een PDF. Receptenboekje, fotoboekje, verhalen bundelen. Full colour geprint, binnen 1 werkdag verzonden.',
                'canonical' => route('landing.boekje'),
                'keywords' => 'boekje maken, eigen boekje printen, pdf naar boekje, boekje laten drukken',
            ],
            'hero' => [
                'title' => 'Je eigen boekje maken',
                'subtitle' => 'Van PDF naar professioneel geprint boekje',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                [
                    'icon' => 'heart',
                    'title' => 'Persoonlijk cadeau',
                    'text' => 'Recepten van oma, verhalen voor de kinderen, herinneringen bundelen.',
                ],
                [
                    'icon' => 'palette',
                    'title' => 'Full colour',
                    'text' => 'Foto\'s en illustraties komen prachtig tot hun recht.',
                ],
                [
                    'icon' => 'zap',
                    'title' => 'Simpel proces',
                    'text' => 'Upload, bestel, klaar. Geen ingewikkelde software nodig.',
                ],
                [
                    'icon' => 'euro',
                    'title' => 'Betaalbaar',
                    'text' => 'Al vanaf €0,15 per pagina. Geen minimale oplage.',
                ],
            ],
            'content' => [
                'intro' => 'Iedereen heeft wel iets dat het waard is om te bundelen. Recepten verzameld over de jaren, verhalen voor je kleinkinderen, foto\'s van een bijzondere reis. Maak er een echt boekje van.',

                'sections' => [
                    [
                        'title' => 'Ideeën voor je boekje',
                        'text' => 'Receptenboekje, reisdagboek, portfolio, familieverhalen, kinderboek, gedichtenbundel, jubileumboek, of gewoon je favoriete artikelen gebundeld.',
                    ],
                    [
                        'title' => 'Hoe maak ik een PDF?',
                        'text' => 'De meeste programma\'s kunnen opslaan als PDF: Word, Pages, Canva, Google Docs. Heb je hulp nodig? Neem contact op.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Wat is het minimale aantal pagina\'s?',
                    'answer' => 'Een boekje heeft minimaal 4 pagina\'s nodig. Het maximum is 64 pagina\'s.',
                ],
                [
                    'question' => 'Waarom gaat een boekje per 4 pagina\'s?',
                    'answer' => 'Elk dubbelgevouwen vel levert 4 pagina\'s op, dus een boekje heeft altijd 4, 8, 12, 16 enzovoort pagina\'s. Heeft je PDF bijvoorbeeld 10 pagina\'s? Dan voegen wij 2 blanco pagina\'s toe aan het eind en telt je boekje 12 pagina\'s. Je betaalt voor het afgeronde aantal.',
                ],
                [
                    'question' => 'Kan ik ook 1 exemplaar bestellen?',
                    'answer' => 'Ja! We hebben geen minimale oplage. Bestel gerust 1 exemplaar.',
                ],
            ],
            'slug' => 'boekje-maken',
        ]);
    }

    /**
     * Handleiding printen - zakelijk/technisch
     */
    public function handleiding(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Handleiding Printen | Instructieboekje Drukken | PrintMijnPDF',
                'description' => 'Print je handleiding of instructieboekje professioneel. Technische documentatie in drukwerkkwaliteit. Binnen 1 werkdag verzonden.',
                'canonical' => route('landing.handleiding'),
                'keywords' => 'handleiding printen, instructieboekje drukken, documentatie printen, gebruiksaanwijzing',
            ],
            'hero' => [
                'title' => 'Handleiding printen',
                'subtitle' => 'Technische documentatie professioneel geprint',
                'cta' => 'Upload je handleiding',
            ],
            'benefits' => [
                [
                    'icon' => 'file-text',
                    'title' => 'Duidelijk leesbaar',
                    'text' => 'Scherpe tekst, heldere schema\'s en diagrammen.',
                ],
                [
                    'icon' => 'palette',
                    'title' => 'Kleur waar nodig',
                    'text' => 'Waarschuwingen, schema\'s en foto\'s in full colour.',
                ],
                [
                    'icon' => 'package',
                    'title' => 'Bij product leveren',
                    'text' => 'Voeg professionele handleidingen toe aan je producten.',
                ],
                [
                    'icon' => 'repeat',
                    'title' => 'Herbestellen',
                    'text' => 'Eenvoudig bijbestellen wanneer je voorraad op is.',
                ],
            ],
            'content' => [
                'intro' => 'Een goede handleiding bespaart supportvragen. Of het nu gaat om productdocumentatie, installatie-instructies of veiligheidsprocedures — geprint materiaal wordt beter bewaard en nageleefd.',

                'sections' => [
                    [
                        'title' => 'Voor wie?',
                        'text' => 'Producenten die handleidingen bij hun producten leveren, technische bedrijven, installateurs, machinebouwers.',
                    ],
                    [
                        'title' => 'Grotere oplages',
                        'text' => 'Voor structurele bestellingen of grote aantallen, neem contact op voor een maatwerkofferte.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Kan ik mijn huisstijl toepassen?',
                    'answer' => 'Wij printen wat je aanlevert. Zorg dat je PDF al in je huisstijl is opgemaakt.',
                ],
                [
                    'question' => 'Welk formaat is standaard?',
                    'answer' => 'We printen A4, A5 en eigen formaten tot en met A3. A5 is handig voor compacte handleidingen bij producten.',
                ],
            ],
            'slug' => 'handleiding-printen',
        ]);
    }

    /**
     * Zakelijk - B2B pagina
     */
    public function zakelijk(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Zakelijk Printen | B2B Printservice | PrintMijnPDF',
                'description' => 'PrintMijnPDF voor bedrijven. Cursusmateriaal, handleidingen, presentaties. Factuur op bedrijfsnaam, snelle levering, professionele kwaliteit.',
                'canonical' => route('landing.zakelijk'),
                'keywords' => 'zakelijk printen, b2b printservice, bedrijven, factuur',
            ],
            'hero' => [
                'title' => 'PrintMijnPDF voor bedrijven',
                'subtitle' => 'Professioneel drukwerk, eenvoudig besteld',
                'cta' => 'Start je bestelling',
            ],
            'benefits' => [
                [
                    'icon' => 'file-text',
                    'title' => 'Factuur op bedrijfsnaam',
                    'text' => 'BTW-specificatie, bedrijfsgegevens, alles netjes geregeld.',
                ],
                [
                    'icon' => 'clock',
                    'title' => 'Snelle levering',
                    'text' => 'Binnen 1 werkdag verzonden. Spoed mogelijk in overleg.',
                ],
                [
                    'icon' => 'shield',
                    'title' => 'Betrouwbare partner',
                    'text' => 'Onderdeel van NIVO print | design | web, 35+ jaar ervaring.',
                ],
                [
                    'icon' => 'phone',
                    'title' => 'Persoonlijk contact',
                    'text' => 'Vragen? Bel ons op 015-219 2525.',
                ],
            ],
            'content' => [
                'intro' => 'PrintMijnPDF is ideaal voor bedrijven die snel en eenvoudig professioneel drukwerk nodig hebben. Geen ingewikkelde offertetrajecten, gewoon uploaden en bestellen.',

                'sections' => [
                    [
                        'title' => 'Waar gebruiken bedrijven ons voor?',
                        'text' => 'Cursusmateriaal voor trainingen, handleidingen bij producten, presentaties voor klanten, interne documentatie, en meer.',
                    ],
                    [
                        'title' => 'Regelmatig nodig?',
                        'text' => 'Neem contact op voor een zakelijke afspraak. We kunnen vaste prijsafspraken maken en factureren op rekening.',
                    ],
                    [
                        'title' => 'Grotere projecten',
                        'text' => 'Voor uitgebreider drukwerk (brochures, flyers, gebonden boeken) kun je terecht bij onze moederorganisatie NIVO print | design | web. Wij verbinden je graag door.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Hoe krijg ik een factuur op bedrijfsnaam?',
                    'answer' => 'Mail na je bestelling naar info@printmijnpdf.nl met je bestelnummer en bedrijfsgegevens (incl. BTW-nummer). We sturen dan een factuur.',
                ],
                [
                    'question' => 'Kan ik op rekening betalen?',
                    'answer' => 'Bij regelmatige bestellingen kunnen we betaling op rekening regelen. Neem contact op om dit te bespreken.',
                ],
                [
                    'question' => 'Zijn er volumekortingen?',
                    'answer' => 'Ja, bij grotere of regelmatige bestellingen maken we graag een maatwerkafspraak. Neem contact op voor een offerte.',
                ],
            ],
            'slug' => 'zakelijk',
        ]);
    }

    /**
     * PDF aanleveren - uitleg over afloop, snijtekens en marges voor het beste resultaat
     */
    public function aanleveren(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'PDF Aanleveren voor Drukwerk | Afloop, Snijtekens en Marges | PrintMijnPDF',
                'description' => 'Zo lever je je PDF aan voor het mooiste drukwerk: 3 mm afloop, snijtekens en een veilige marge van 5 of 8 mm. Met uitleg voor Canva en Word.',
                'canonical' => route('landing.aanleveren'),
                'keywords' => 'pdf aanleveren, afloop, snijtekens, drukklare pdf, canva afloop, marge drukwerk',
                'service_type' => 'PDF printen',
            ],
            'breadcrumb' => 'PDF aanleveren',
            'hero' => [
                'title' => 'PDF aanleveren voor het beste resultaat',
                'subtitle' => 'Afloop, snijtekens en marges kort uitgelegd',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'file-text', 'title' => 'Elke PDF kan', 'text' => 'Ook zonder afloop printen we je bestand: we vullen de rand dan voor je aan.'],
                ['icon' => 'shield', 'title' => 'Geen witte randjes', 'text' => 'Met 3 mm afloop lopen kleuren en foto\'s na het snijden echt tot de rand.'],
                ['icon' => 'book', 'title' => 'Niets valt weg', 'text' => 'Met een veilige marge blijft je tekst ver genoeg van de snijkant en de vouw.'],
                ['icon' => 'palette', 'title' => 'Tips voor Canva', 'text' => 'Stap voor stap een drukklare PDF met afloop en snijtekens downloaden.'],
            ],
            'content' => [
                'intro' => 'Je kunt elke PDF bij ons laten printen. Heb je geen bestand met afloop, dan kies je bij het bestellen: wij vullen de rand voor je aan, of we snijden je drukwerk iets kleiner. Het beste resultaat krijg je met een PDF met 3 mm afloop en snijtekens. Hieronder lees je wat dat is, waarom het helpt en hoe je zo\'n bestand maakt.',
                'sections' => [
                    [
                        'title' => 'Waarom afloop en snijtekens nodig zijn',
                        'text' => [
                            'We printen je drukwerk op een groter vel en snijden het daarna op maat. Een snijmachine snijdt heel nauwkeurig, maar niet tot op de tiende millimeter. Loopt een achtergrondkleur of foto precies tot de rand van je pagina, dan kan er na het snijden een dun wit randje zichtbaar zijn.',
                            'Daarom laat je kleuren en foto\'s 3 mm over de rand van je pagina doorlopen. Dat stukje heet de afloop en wordt er bij het snijden afgesneden. De snijtekens zijn kleine lijntjes in de hoeken buiten je pagina: daaraan ziet de snijmachine precies waar de snijkant ligt.',
                        ],
                        'figure' => 'afloop',
                    ],
                    [
                        'title' => 'Houd een veilige marge aan',
                        'text' => 'Tekst, logo\'s, paginanummers en kaders zet je niet tot aan de rand, maar op veilige afstand van de snijkant. Zo valt er bij het snijden niets weg en komt bij een boekje niets in de vouw terecht.',
                        'list' => [
                            'PDF met afloop: houd minimaal 5 mm afstand tot de snijkant.',
                            'PDF zonder afloop: houd minimaal 8 mm afstand tot de rand. Dan blijft je tekst veilig, welke keuze je bij het bestellen ook maakt.',
                        ],
                        'after' => 'De marge geldt rondom, ook aan de kant van de vouw bij een boekje. Achtergronden en foto\'s die tot de rand lopen mogen gewoon blijven staan; het gaat alleen om tekst en andere belangrijke onderdelen.',
                    ],
                    [
                        'title' => 'Geen afloop? Dan vullen we de rand aan',
                        'text' => 'Heb je alleen een gewone PDF zonder afloop, bijvoorbeeld uit Word? Geen probleem. Na het uploaden kies je wat we doen:',
                        'list' => [
                            'Rand aanvullen (aanbevolen): we laten je ontwerp rondom 3 mm doorlopen door de rand van elke pagina te spiegelen, en snijden dat stukje er daarna weer af. Je drukwerk komt precies op het formaat van je PDF uit, zonder witte randjes, en aan je ontwerp zelf verandert niets. Je ziet vooraf een voorbeeld.',
                            'Iets kleiner snijden: we printen je PDF zoals hij is en snijden er rondom een klein stukje af. Je drukwerk wordt dan iets kleiner dan het formaat van je PDF.',
                        ],
                        'after' => 'Bij een PDF met invulvelden kunnen we de rand niet automatisch aanvullen; die snijden we iets kleiner. Wil je zelf bepalen hoe je ontwerp over de rand doorloopt? Lever dan een PDF met afloop en snijtekens aan.',
                    ],
                    [
                        'title' => 'Een PDF met afloop en snijtekens maken in Canva',
                        'text' => 'Begin je ontwerp in Canva als printontwerp (bijvoorbeeld een A4- of A5-document). Daarna:',
                        'ordered' => true,
                        'list' => [
                            'Ga naar Bestand › Instellingen en zet Afloop afdrukken tonen aan. Er verschijnt een rand rond je pagina.',
                            'Laat achtergronden en foto\'s doorlopen tot over die rand.',
                            'Houd tekst en kaders minimaal 5 mm binnen je pagina.',
                            'Klik op Delen › Downloaden en kies bij Bestandstype de PDF voor afdrukken.',
                            'Vink Bijsnijmarkeringen en afloop aan en download je PDF.',
                        ],
                        'after' => 'Is je ontwerp begonnen als een ander soort ontwerp, zoals een presentatie, dan kun je er in de gratis versie van Canva geen afloop aan toevoegen. Houd dan een marge van 8 mm aan; bij het bestellen vullen we de rand voor je aan.',
                    ],
                    [
                        'title' => 'Een PDF maken in Word of een ander programma',
                        'text' => [
                            'Word kent geen afloop. Houd daarom rondom minimaal 8 mm marge aan en kies Opslaan als › PDF. Bij het bestellen vullen we de rand voor je aan.',
                            'Werk je met een opmaakprogramma zoals Adobe InDesign of Affinity Publisher, stel dan in je document 3 mm afloop in en exporteer een PDF voor drukwerk met snijtekens en afloop.',
                        ],
                    ],
                    [
                        'title' => 'Verder nog handig om te weten',
                        'list' => [
                            'Eén formaat per bestelling: alle pagina\'s in je PDF moeten even groot zijn. Heb je verschillende formaten, plaats dan aparte bestellingen.',
                            'Elk formaat tot en met A3 kan. Een geniet boekje kan tot 222 × 304 mm per pagina; groter printen we als losse pagina\'s.',
                            'Lever een boekje aan in de gewone leesvolgorde, met pagina 1 als voorkant. Wij zetten de pagina\'s zelf in de juiste volgorde.',
                            'Gebruik foto\'s in goede kwaliteit (bij voorkeur 300 dpi op ware grootte), dan worden ze scherp geprint.',
                        ],
                    ],
                ],
            ],
            'howto' => [
                'name' => 'Een PDF met afloop en snijtekens downloaden in Canva',
                'steps' => [
                    ['name' => 'Afloop tonen', 'text' => 'Ga naar Bestand › Instellingen en zet Afloop afdrukken tonen aan.'],
                    ['name' => 'Ontwerp doortrekken', 'text' => 'Laat achtergronden en foto\'s doorlopen tot over de rand van de afloop.'],
                    ['name' => 'Marge aanhouden', 'text' => 'Houd tekst en kaders minimaal 5 mm binnen je pagina.'],
                    ['name' => 'PDF voor afdrukken kiezen', 'text' => 'Klik op Delen › Downloaden en kies de PDF voor afdrukken.'],
                    ['name' => 'Snijtekens en afloop aanvinken', 'text' => 'Vink Bijsnijmarkeringen en afloop aan en download je PDF.'],
                ],
            ],
            'faq' => [
                ['question' => 'Moet mijn PDF afloop hebben?', 'answer' => 'Nee. Zonder afloop printen we je bestand ook. Bij het bestellen kies je of we de rand voor je aanvullen (we spiegelen de rand van je pagina 3 mm naar buiten) of je drukwerk iets kleiner snijden. Houd in beide gevallen minimaal 8 mm marge aan. Met een eigen PDF met 3 mm afloop en snijtekens bepaal je zelf precies hoe je ontwerp doorloopt.'],
                ['question' => 'Hoeveel marge moet ik aanhouden?', 'answer' => 'Met afloop minimaal 5 mm vanaf de snijkant, zonder afloop minimaal 8 mm vanaf de rand. Dat geldt voor tekst, logo\'s, paginanummers en kaders, rondom en ook aan de vouwkant van een boekje.'],
                ['question' => 'Wat zijn snijtekens?', 'answer' => 'Kleine lijntjes in de hoeken buiten je pagina die aangeven waar gesneden moet worden. Canva zet ze erbij als je bij het downloaden Bijsnijmarkeringen en afloop aanvinkt.'],
                ['question' => 'Wordt mijn PDF met afloop als groter formaat gezien?', 'answer' => 'Nee. We lezen het snijformaat uit je PDF, dus een A4 met afloop en snijtekens wordt gewoon als A4 herkend en geprijsd.'],
            ],
            'slug' => 'pdf-aanleveren',
        ]);
    }

    /**
     * PDF naar boekje - informatieve zoekintentie ("hoe maak ik een boekje van mijn PDF")
     */
    public function pdfNaarBoekje(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'PDF naar Boekje | Zelf Afdrukken of Laten Printen | PrintMijnPDF',
                'description' => 'Zo maak je van een PDF een boekje: stap voor stap zelf afdrukken met Acrobat Reader of Word, of laat het printen als geniet boekje. Full colour, binnen 1 werkdag verzonden.',
                'canonical' => route('landing.pdf-naar-boekje'),
                'keywords' => 'pdf naar boekje, pdf als boekje printen, pdf boekje afdrukken, boekje printen van pdf',
                'service_type' => 'PDF als boekje printen',
            ],
            'breadcrumb' => 'PDF naar boekje',
            'hero' => [
                'title' => 'PDF naar boekje',
                'subtitle' => 'Zelf afdrukken of in één keer professioneel laten printen',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'file-text', 'title' => 'Gewone PDF uploaden', 'text' => 'Je hoeft niets om te zetten. Wij zetten de pagina\'s zelf in de juiste boekjesvolgorde.'],
                ['icon' => 'palette', 'title' => 'Full colour', 'text' => 'Geprint in echte drukwerkkwaliteit, met heldere kleuren en scherpe tekst.'],
                ['icon' => 'package', 'title' => 'Gevouwen en geniet', 'text' => 'Je ontvangt een kant-en-klaar boekje, geen losse vellen om zelf te vouwen.'],
                ['icon' => 'clock', 'title' => 'Binnen 1 werkdag verzonden', 'text' => 'Bestel vóór 11:00 en je boekje gaat dezelfde dag op de post met PostNL.'],
            ],
            'content' => [
                'intro' => 'Een boekje maken van een PDF kan op twee manieren: je drukt het thuis zelf af met de boekjesfunctie van je printprogramma, of je laat het printen. Hieronder lees je hoe allebei werkt en waar je op moet letten.',
                'sections' => [
                    [
                        'title' => 'Hoe werkt een boekje van een PDF?',
                        'text' => [
                            'Een boekje bestaat uit vellen die dubbelgevouwen en in de rug geniet worden. Op elk vel staan vier pagina\'s: twee aan de voorkant en twee aan de achterkant. Daarom komen de pagina\'s niet in de gewone volgorde op het papier. Bij een boekje van 8 pagina\'s staan bijvoorbeeld pagina 8 en 1 naast elkaar op de buitenkant van het eerste vel.',
                            'Het aantal pagina\'s van een boekje is daarom altijd een veelvoud van 4: 4, 8, 12, 16 enzovoort. Heeft je PDF een ander aantal, dan komen er lege pagina\'s bij.',
                        ],
                    ],
                    [
                        'title' => 'Zelf een PDF als boekje afdrukken met Adobe Acrobat Reader',
                        'text' => 'De gratis Adobe Acrobat Reader heeft een ingebouwde boekjesfunctie die de pagina\'s automatisch in de juiste volgorde zet.',
                        'ordered' => true,
                        'list' => [
                            'Open je PDF in Adobe Acrobat Reader.',
                            'Kies Afdrukken (Ctrl+P, of Cmd+P op een Mac).',
                            'Kies bij de instellingen voor paginagrootte en -verwerking de optie Boekje.',
                            'Laat beide zijden afdrukken en kies dubbelzijdig printen met omslaan langs de korte zijde.',
                            'Druk af, vouw de stapel vellen dubbel en niet het boekje in de vouw.',
                        ],
                    ],
                    [
                        'title' => 'Een boekje maken in Word',
                        'text' => 'Begin je in Word? Open dan Pagina-instelling en kies bij Meerdere pagina\'s de optie Boek vouwen. Word zet je document dan om naar een boekjesindeling. Sla het daarna op als PDF of druk het direct dubbelzijdig af.',
                    ],
                    [
                        'title' => 'Waar het thuis vaak misgaat',
                        'list' => [
                            'Je printer moet dubbelzijdig kunnen printen, anders moet je elk vel met de hand omdraaien.',
                            'Kies je de verkeerde omslagkant, dan staat de achterkant van elk vel op z\'n kop.',
                            'Thuis print je op A4-papier. Een A4-document wordt daardoor verkleind tot een A5-boekje.',
                            'Een gewone nietmachine komt niet bij het midden van de vouw. Daarvoor heb je een nietmachine met een lange arm nodig.',
                            'Bij veel kleur of foto\'s is je inktpatroon snel leeg en is de kwaliteit vaak minder dan je hoopt.',
                        ],
                    ],
                    [
                        'title' => 'Liever je PDF als boekje laten printen?',
                        'text' => [
                            'Bij PrintMijnPDF upload je gewoon je PDF in de normale leesvolgorde. Wij zetten de pagina\'s in de juiste volgorde, printen in full colour, vouwen en nieten. Een A4-document blijft een A4-boekje: we printen het op liggend A3 en vouwen het dubbel. Een A5-document printen we op liggend A4.',
                            'Je ziet direct na het uploaden wat je boekje kost, en je kunt al vanaf 1 exemplaar bestellen.',
                        ],
                    ],
                ],
            ],
            'howto' => [
                'name' => 'Een PDF als boekje afdrukken met Adobe Acrobat Reader',
                'steps' => [
                    ['name' => 'PDF openen', 'text' => 'Open je PDF in Adobe Acrobat Reader.'],
                    ['name' => 'Afdrukken kiezen', 'text' => 'Kies Afdrukken (Ctrl+P of Cmd+P).'],
                    ['name' => 'Boekje kiezen', 'text' => 'Kies bij paginagrootte en -verwerking de optie Boekje.'],
                    ['name' => 'Dubbelzijdig instellen', 'text' => 'Druk beide zijden af, omslaan langs de korte zijde.'],
                    ['name' => 'Vouwen en nieten', 'text' => 'Vouw de vellen dubbel en niet het boekje in de vouw.'],
                ],
            ],
            'faq' => [
                ['question' => 'Moet ik mijn PDF zelf in boekjesvolgorde zetten?', 'answer' => 'Nee. Upload je PDF in de gewone leesvolgorde, met pagina 1 als voorkant. Wij zetten de pagina\'s zelf in de juiste volgorde voor het boekje. Upload dus geen PDF die al als boekje is opgemaakt.'],
                ['question' => 'Kan ik een A4-PDF als A4-boekje laten printen?', 'answer' => 'Ja. Een A4-boekje printen we op liggend A3 en vouwen we dubbel, zodat elke pagina A4 blijft. Een A5-boekje printen we op liggend A4.'],
                ['question' => 'Hoeveel pagina\'s mag mijn boekje hebben?', 'answer' => 'Een geniet boekje heeft 4 tot 64 pagina\'s en altijd een veelvoud van 4. Heeft je PDF bijvoorbeeld 10 pagina\'s, dan voegen wij 2 blanco pagina\'s toe aan het eind.'],
                ['question' => 'Hoe snel heb ik mijn boekje?', 'answer' => 'Bestel je vóór 11:00 op een werkdag, dan versturen we je boekje dezelfde dag (bij meer dan 50 exemplaren de volgende werkdag) en bezorgt PostNL het binnen 2 werkdagen. Afhalen in Delfgauw kan vanaf de volgende werkdag.'],
            ],
            'slug' => 'pdf-naar-boekje',
        ]);
    }

    /**
     * PDF laten printen - algemene transactionele zoekintentie
     */
    public function pdfLatenPrinten(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'PDF Laten Printen | Online Uploaden, Binnen 1 Werkdag Verzonden | PrintMijnPDF',
                'description' => 'PDF laten printen zonder gedoe: upload je bestand, kies boekje of losse pagina\'s en betaal met iDEAL. Full colour drukwerkkwaliteit vanaf €0,15 per pagina.',
                'canonical' => route('landing.pdf'),
                'keywords' => 'pdf laten printen, pdf printen, pdf online printen, pdf afdrukken laten, document laten printen',
                'service_type' => 'PDF printen',
            ],
            'breadcrumb' => 'PDF laten printen',
            'hero' => [
                'title' => 'PDF laten printen',
                'subtitle' => 'Upload je bestand, wij printen en bezorgen het',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'zap', 'title' => 'Direct de prijs', 'text' => 'Na het uploaden zie je meteen het aantal pagina\'s, het formaat en de prijs.'],
                ['icon' => 'palette', 'title' => 'Drukwerkkwaliteit', 'text' => 'Geprint in full colour door een professionele drukkerij, niet op een kantoorprinter.'],
                ['icon' => 'euro', 'title' => 'Vanaf €0,15 per pagina', 'text' => 'Geen abonnement en geen minimale oplage. Je betaalt alleen wat je print.'],
                ['icon' => 'clock', 'title' => 'Binnen 1 werkdag verzonden', 'text' => 'Bezorgd met PostNL of gratis af te halen in Delfgauw.'],
            ],
            'content' => [
                'intro' => 'Geen printer thuis, of wil je dat je document er echt goed uitziet? Laat je PDF dan printen. Je uploadt het bestand, kiest hoe je het wilt hebben en betaalt met iDEAL. Wij printen, werken af en versturen.',
                'sections' => [
                    [
                        'title' => 'Welke PDF\'s kun je laten printen?',
                        'text' => 'Scripties, readers, cursusmateriaal, handleidingen, rapporten, verslagen, receptenboekjes, portfolio\'s en programmaboekjes. Meestal A4 of A5, maar een eigen formaat kan ook: als boekje tot 222 × 304 mm per pagina, als losse pagina\'s tot en met A3.',
                    ],
                    [
                        'title' => 'Boekje of losse pagina\'s?',
                        'list' => [
                            'Geniet boekje: dubbelzijdig geprint, gevouwen en in de rug geniet. Geschikt voor 4 tot 64 pagina\'s.',
                            'Losse pagina\'s: enkel- of dubbelzijdig, ongebonden. Handig voor grotere documenten of als je zelf in een map of ringband wilt opbergen.',
                        ],
                    ],
                    [
                        'title' => 'Zo lever je je PDF goed aan',
                        'list' => [
                            'Gebruik staand A4 (210 × 297 mm) of staand A5 (148 × 210 mm).',
                            'Sla je document op als PDF met ingesloten lettertypen. Word, Google Docs, Pages en Canva doen dat standaard.',
                            'Gebruik afbeeldingen van goede kwaliteit; foto\'s van internet zien er geprint vaak korrelig uit.',
                            'Wil je dat kleur of een foto tot aan de rand van het papier doorloopt? Laat dan 3 mm afloop rondom staan.',
                        ],
                    ],
                    [
                        'title' => 'Geprint door een echte drukkerij',
                        'text' => 'PrintMijnPDF is onderdeel van NIVO print | design | web, een professionele drukkerij sinds 1980. Je document wordt dus niet op een kantoorprinter geprint, maar op drukwerkmachines met de kleurkwaliteit die je kent van tijdschriften en brochures.',
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Kan ik mijn PDF enkelzijdig laten printen?', 'answer' => 'Ja, bij losse pagina\'s kies je zelf enkel- of dubbelzijdig. Een geniet boekje is altijd dubbelzijdig.'],
                ['question' => 'Kan ik mijn bestelling ophalen?', 'answer' => 'Ja, afhalen is gratis en kan vanaf de volgende werkdag tussen 17:00 en 17:30 bij NIVO print | design | web, Exportweg 11 in Delfgauw.'],
                ['question' => 'Hoe betaal ik?', 'answer' => 'Je betaalt veilig met iDEAL via Mollie. Pas na de betaling gaat je bestelling in productie.'],
                ['question' => 'Kan ik meerdere exemplaren bestellen?', 'answer' => 'Ja. Je kiest het aantal exemplaren bij het bestellen en ziet direct de totaalprijs.'],
            ],
            'slug' => 'pdf-laten-printen',
        ]);
    }

    /**
     * Boekje printen - A4/A5 geniet boekje, prijsgerichte zoekintentie
     */
    public function boekjePrinten(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Boekje Printen | A4 en A5 Boekje Laten Drukken | PrintMijnPDF',
                'description' => 'Boekje printen vanaf 1 exemplaar: A4 of A5, full colour en geniet. Upload je PDF en zie direct de prijs. Binnen 1 werkdag verzonden of gratis afhalen.',
                'canonical' => route('landing.boekje-printen'),
                'keywords' => 'boekje printen, boekje laten drukken, a5 boekje printen, a4 boekje printen, geniet boekje, brochure printen',
                'service_type' => 'Boekje printen',
            ],
            'breadcrumb' => 'Boekje printen',
            'hero' => [
                'title' => 'Boekje printen in A4 of A5',
                'subtitle' => 'Full colour, gevouwen en geniet, vanaf 1 exemplaar',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'book', 'title' => 'A4 of A5', 'text' => 'We herkennen het formaat van je PDF automatisch.'],
                ['icon' => 'palette', 'title' => 'Full colour', 'text' => 'Elke pagina in kleur, zonder meerprijs voor kleur.'],
                ['icon' => 'users', 'title' => 'Vanaf 1 exemplaar', 'text' => 'Geen minimale oplage. Elk extra exemplaar wordt voordeliger.'],
                ['icon' => 'clock', 'title' => 'Snel geleverd', 'text' => 'Binnen 1 werkdag verzonden met PostNL, of gratis afhalen in Delfgauw.'],
            ],
            'content' => [
                'intro' => 'Een geniet boekje is de snelste manier om van een PDF iets tastbaars te maken: een programmaboekje, brochure, reader, receptenboek of verslag. Je uploadt je PDF en bestelt in een paar minuten.',
                'sections' => [
                    [
                        'title' => 'A4-boekje of A5-boekje',
                        'list' => [
                            'A4-boekje: we printen op liggend A3 en vouwen dubbel, zodat elke pagina A4 is. Goed voor rapporten, readers en scripties.',
                            'A5-boekje: we printen op liggend A4 en vouwen dubbel. Handzaam formaat voor programmaboekjes, receptenboekjes en flyers met meer tekst.',
                        ],
                        'after' => 'Je hoeft niets in te stellen: het formaat halen we uit je PDF.',
                    ],
                    [
                        'title' => 'Hoeveel pagina\'s?',
                        'text' => 'Een geniet boekje heeft 4 tot 64 pagina\'s. Omdat elk gevouwen vel 4 pagina\'s oplevert, is het aantal altijd een veelvoud van 4. Heeft je PDF bijvoorbeeld 10 pagina\'s? Dan voegen we 2 blanco pagina\'s toe aan het eind en betaal je voor 12. Bij meer dan 64 pagina\'s printen we je document als losse pagina\'s.',
                    ],
                    [
                        'title' => 'Zo wordt je boekje gemaakt',
                        'ordered' => true,
                        'list' => [
                            'We zetten de pagina\'s van je PDF in de juiste volgorde op de vellen.',
                            'De vellen worden dubbelzijdig in full colour geprint.',
                            'We vouwen de vellen en nieten ze in de rug tot één boekje.',
                            'Je boekje wordt verstuurd met PostNL of ligt klaar om af te halen.',
                        ],
                    ],
                    [
                        'title' => 'Waarvoor kies je een geniet boekje?',
                        'text' => 'Voor alles wat je wilt doorbladeren: programmaboekjes voor een evenement of uitvaart, brochures, nieuwsbrieven, readers, verslagen, kinderboekjes, receptenboekjes en portfolio\'s.',
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Wat kost een boekje printen?', 'answer' => 'De prijs bestaat uit eenmalige startkosten, een prijs per pagina (A4 € 0,15, A5 € 0,10), nieten per boekje en verzending. In de tabel op deze pagina zie je voorbeelden; na het uploaden van je PDF zie je de exacte prijs.'],
                ['question' => 'Kan ik meerdere exemplaren van mijn boekje bestellen?', 'answer' => 'Ja. Het nieten kost € 5,00 voor het eerste boekje en € 2,50 voor elk volgend exemplaar.'],
                ['question' => 'Wat als mijn PDF meer dan 64 pagina\'s heeft?', 'answer' => 'Dan printen we je document als losse pagina\'s. Wil je een andere afwerking, zoals een ringband of lijmbinding? Neem dan contact op via info@printmijnpdf.nl.'],
                ['question' => 'Kan ik een boekje enkelzijdig laten printen?', 'answer' => 'Nee, een geniet boekje is altijd dubbelzijdig. Wil je een pagina leeg laten, voeg dan een lege pagina toe aan je PDF.'],
            ],
            'slug' => 'boekje-printen',
        ]);
    }

    /**
     * Spoed - "vandaag/morgen nodig", met afhalen in Delfgauw
     */
    public function spoed(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'PDF Printen met Spoed | Morgen Afhalen bij Delft | PrintMijnPDF',
                'description' => 'PDF of boekje met spoed nodig? Vóór 11:00 besteld is vandaag verzonden, of haal het de volgende werkdag gratis af in Delfgauw (bij Delft). Zelfde dag in overleg.',
                'canonical' => route('landing.spoed'),
                'keywords' => 'pdf printen spoed, boekje printen spoed, snel printen delft, printen afhalen delft, drukwerk morgen klaar',
                'service_type' => 'Spoed PDF printen',
            ],
            'breadcrumb' => 'PDF printen met spoed',
            'hero' => [
                'title' => 'PDF printen met spoed',
                'subtitle' => 'Vandaag bestellen, morgen afhalen bij Delft',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'clock', 'title' => 'Morgen afhalen', 'text' => 'Gratis afhalen vanaf de volgende werkdag, 17:00–17:30 in Delfgauw.'],
                ['icon' => 'zap', 'title' => 'Zelfde dag in overleg', 'text' => 'Bel 015-219 2525, dan kijken we wat er nog kan.'],
                ['icon' => 'package', 'title' => 'Of laten bezorgen', 'text' => 'Vóór 11:00 besteld: tot 50 exemplaren vandaag verzonden, binnen 2 werkdagen bezorgd door PostNL.'],
                ['icon' => 'euro', 'title' => 'Geen spoedtoeslag', 'text' => 'Afhalen is gratis; je betaalt alleen het printwerk.'],
            ],
            'content' => [
                'intro' => 'Moet je scriptie, programmaboekje of reader er morgen al liggen? Bestel online en haal je drukwerk de volgende werkdag op bij onze drukkerij in Delfgauw, tussen Delft, Den Haag, Zoetermeer en Rotterdam. Afhalen kost niets en er is geen spoedtoeslag.',
                'sections' => [
                    [
                        'title' => 'Hoe snel is het klaar?',
                        'list' => [
                            'Afhalen: gratis, vanaf de volgende werkdag tussen 17:00 en 17:30 bij NIVO print | design | web, Exportweg 11, 2645 ED Delfgauw.',
                            'Zelfde dag: niet standaard. Bel 015-219 2525 voordat je bestelt, dan kijken we samen wat er mogelijk is.',
                            'Bezorgen: bestel je vóór 11:00 op een werkdag, dan versturen we tot 50 exemplaren dezelfde dag en bezorgt PostNL het binnen 2 werkdagen. Grotere oplagen gaan de volgende werkdag de deur uit.',
                        ],
                    ],
                    [
                        'title' => 'Zo bestel je met spoed',
                        'ordered' => true,
                        'list' => [
                            'Upload je PDF op printmijnpdf.nl; je ziet direct het aantal pagina\'s en de prijs.',
                            'Kies geniet boekje of losse pagina\'s, A4 of A5.',
                            'Kies "Afhalen" als bezorgmethode en betaal met iDEAL.',
                            'Haal je drukwerk de volgende werkdag tussen 17:00 en 17:30 op.',
                        ],
                    ],
                    [
                        'title' => 'Wat kun je met spoed laten printen?',
                        'text' => 'Alles wat we normaal ook printen: geniet boekjes van 4 tot 64 pagina\'s en losse pagina\'s, in elk formaat tot en met A3, full colour. Denk aan scripties, programmaboekjes voor een uitvaart of evenement, readers, handleidingen en presentaties. Ander drukwerk nodig? Neem contact op, dan kijken we of de drukkerij het kan maken.',
                    ],
                    [
                        'title' => 'Spoed zonder fouten',
                        'text' => 'Bij haast gaat het vaak mis in het bestand, niet in het printen. Controleer daarom vóór het uploaden of je PDF het formaat heeft waarin je hem gedrukt wilt hebben en of de pagina\'s in de goede volgorde staan. Voor een boekje zetten wij de pagina\'s zelf in de juiste volgorde op de vellen; jij levert gewoon een PDF met pagina 1, 2, 3 enzovoort aan.',
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Kan ik mijn PDF vandaag nog laten printen?', 'answer' => 'Standaard haal je je bestelling de volgende werkdag op. Heb je het dezelfde dag nodig? Bel dan 015-219 2525, dan kijken we wat er mogelijk is.'],
                ['question' => 'Waar kan ik mijn drukwerk afhalen?', 'answer' => 'Bij NIVO print | design | web, Exportweg 11, 2645 ED Delfgauw (gemeente Pijnacker-Nootdorp, naast Delft). Op werkdagen tussen 17:00 en 17:30.'],
                ['question' => 'Kost spoed extra?', 'answer' => 'Nee. Afhalen is gratis en er is geen spoedtoeslag; je betaalt startkosten, de pagina\'s en eventueel het nieten.'],
                ['question' => 'Ik woon niet in de buurt van Delft, hoe snel kan het dan?', 'answer' => 'Dan versturen we met PostNL. Bestel je vóór 11:00 op een werkdag, dan gaat je pakket (tot 50 exemplaren) dezelfde dag de deur uit en bezorgt PostNL het binnen 2 werkdagen.'],
            ],
            'slug' => 'pdf-printen-met-spoed',
        ]);
    }

    /**
     * Prijzen - "wat kost een pdf printen", alle bedragen uit config/pricing
     */
    public function prijzen(): View
    {
        $p = fn (string $key) => self::euro((int) config("pricing.{$key}"));
        $total = fn (int $pages, string $format, string $binding, string $delivery = 'shipping', int $qty = 1) => self::euro(Order::calculatePrice($pages, $format, $binding, $delivery, $qty)['total']);

        return $this->page([
            'meta' => [
                'title' => 'Wat Kost een PDF Printen? Prijzen en Rekenvoorbeelden | PrintMijnPDF',
                'description' => 'PDF printen kost bij PrintMijnPDF ' . $p('per_page_a4') . ' per A4-pagina en ' . $p('per_page_a5') . ' per A5-pagina, plus ' . $p('startup') . ' startkosten. Bekijk rekenvoorbeelden voor boekjes en losse pagina\'s.',
                'canonical' => route('landing.prijzen'),
                'keywords' => 'pdf printen prijs, wat kost pdf printen, boekje printen kosten, printen per pagina prijs, kosten scriptie printen',
                'service_type' => 'PDF printen',
            ],
            'breadcrumb' => 'Prijzen',
            'hero' => [
                'title' => 'Wat kost een PDF printen?',
                'subtitle' => 'Alle prijzen op een rij, zonder verborgen kosten',
                'cta' => 'Bereken je prijs',
            ],
            'benefits' => [
                ['icon' => 'euro', 'title' => $p('per_page_a4') . ' per A4-pagina', 'text' => 'Full colour, enkel- of dubbelzijdig. A5: ' . $p('per_page_a5') . ' per pagina.'],
                ['icon' => 'book', 'title' => 'Nieten ' . $p('binding'), 'text' => 'Voor het eerste boekje; elk extra exemplaar ' . $p('binding_extra') . '.'],
                ['icon' => 'package', 'title' => 'Verzenden ' . $p('shipping'), 'text' => 'Per bestelling, ongeacht het aantal. Afhalen is gratis.'],
                ['icon' => 'zap', 'title' => 'Direct de exacte prijs', 'text' => 'Upload je PDF en je ziet meteen wat het kost.'],
            ],
            'content' => [
                'intro' => 'Bij PrintMijnPDF bestaat de prijs uit vier onderdelen: eenmalige startkosten, een prijs per pagina, nieten als je een boekje kiest, en verzending. Alle prijzen zijn inclusief btw. Hieronder staan de bedragen en een paar rekenvoorbeelden.',
                'sections' => [
                    [
                        'title' => 'Zo is de prijs opgebouwd',
                        'list' => [
                            'Startkosten: ' . $p('startup') . ' per bestelling.',
                            'Per pagina: ' . $p('per_page_a4') . ' (A4) of ' . $p('per_page_a5') . ' (A5), full colour.',
                            'Nieten (geniet boekje): ' . $p('binding') . ' voor het eerste exemplaar, ' . $p('binding_extra') . ' voor elk volgend exemplaar.',
                            'Verzending: ' . $p('shipping') . ' per bestelling, of gratis afhalen in Delfgauw.',
                        ],
                    ],
                    [
                        'title' => 'Rekenvoorbeelden',
                        'list' => [
                            "Scriptie, A4-boekje van 48 pagina's, 1 exemplaar, verzonden: " . $total(48, 'A4', 'booklet'),
                            "Scriptie, A4-boekje van 48 pagina's, 3 exemplaren, verzonden: " . $total(48, 'A4', 'booklet', 'shipping', 3),
                            "Programmaboekje, A5 van 16 pagina's, 1 exemplaar, afhalen: " . $total(16, 'A5', 'booklet', 'pickup'),
                            "Reader, 100 losse A4-pagina's, verzonden: " . $total(100, 'A4', 'loose'),
                        ],
                    ],
                    [
                        'title' => 'Waarom een boekje per 4 pagina\'s wordt gerekend',
                        'text' => 'Een boekje bestaat uit dubbelgevouwen vellen en elk vel levert 4 pagina\'s op. Heeft je PDF 10 pagina\'s, dan voegen we 2 blanco pagina\'s toe en betaal je voor 12. Bij losse pagina\'s betaal je precies het aantal pagina\'s in je PDF.',
                    ],
                    [
                        'title' => 'Zo houd je het goedkoop',
                        'list' => [
                            'Kies A5 als je document dat toelaat: een A5-pagina kost ' . $p('per_page_a5') . ' in plaats van ' . $p('per_page_a4') . '.',
                            'Bestel meerdere exemplaren in één keer: de startkosten en verzending betaal je maar één keer en elk extra boekje nieten kost ' . $p('binding_extra') . '.',
                            'Haal je bestelling af in Delfgauw, dan betaal je geen verzendkosten.',
                        ],
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Wat kost het om één PDF-pagina te printen?', 'answer' => 'Een A4-pagina kost ' . $p('per_page_a4') . ' en een A5-pagina ' . $p('per_page_a5') . ', in full colour. Daarbij komen eenmalig ' . $p('startup') . ' startkosten per bestelling en eventueel verzending.'],
                ['question' => 'Is dubbelzijdig printen duurder?', 'answer' => 'Je betaalt per pagina van je PDF. Dubbelzijdig printen kost dus hetzelfde per pagina, je krijgt alleen minder vellen papier.'],
                ['question' => 'Zijn de prijzen inclusief btw?', 'answer' => 'Ja, alle prijzen op PrintMijnPDF zijn inclusief btw.'],
                ['question' => 'Kan ik een zakelijke factuur krijgen?', 'answer' => 'Ja. Mail je bestelnummer en bedrijfsgegevens naar info@printmijnpdf.nl, dan sturen we je een factuur met btw-specificatie.'],
            ],
            'slug' => 'prijzen',
        ]);
    }

    /**
     * Liturgie / programmaboekje voor een uitvaart: haast, regio Delft, afhalen
     */
    public function uitvaart(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Liturgie Uitvaart Printen | Morgen Afhalen | PrintMijnPDF',
                'description' => 'Liturgie voor een uitvaart laten printen: full colour geniet boekje in A5 of A4. Vandaag bestellen, morgen gratis afhalen bij Delft of verzonden met PostNL.',
                'canonical' => route('landing.uitvaart'),
                'keywords' => 'liturgie uitvaart printen, rouwboekje printen, programmaboekje uitvaart, liturgie begrafenis drukken, liturgie crematie printen',
                'service_type' => 'Liturgie uitvaart printen',
            ],
            'breadcrumb' => 'Liturgie uitvaart printen',
            'hero' => [
                'title' => 'Liturgie voor een uitvaart printen',
                'subtitle' => 'Een mooi boekje voor het afscheid, zonder extra zorgen',
                'cta' => 'Upload je liturgie',
            ],
            'benefits' => [
                ['icon' => 'clock', 'title' => 'Morgen klaar', 'text' => 'Vandaag bestellen, de volgende werkdag gratis afhalen in Delfgauw. Zelfde dag in overleg.'],
                ['icon' => 'palette', 'title' => 'Foto\'s in full colour', 'text' => 'Een foto op de voorkant en kleur in het hele boekje, in drukwerkkwaliteit.'],
                ['icon' => 'book', 'title' => 'Gevouwen en geniet', 'text' => 'Een net boekje in A5 of A4, van 4 tot 64 pagina\'s.'],
                ['icon' => 'phone', 'title' => 'Persoonlijk contact', 'text' => 'Twijfel of haast? Bel 015-219 2525, dan denken we met je mee.'],
            ],
            'content' => [
                'intro' => 'Tussen het overlijden en de uitvaart zit vaak maar een paar dagen. De liturgie of het programmaboekje moet dan snel en goed gedrukt worden. Bij PrintMijnPDF upload je de PDF, zie je meteen de prijs, en haal je de boekjes de volgende werkdag op bij onze drukkerij in Delfgauw, vlak bij Delft. Of we versturen ze met PostNL.',
                'sections' => [
                    [
                        'title' => 'Wat staat er meestal in een liturgie?',
                        'list' => [
                            'Een foto en de naam van de overledene op de voorkant, met geboorte- en sterfdatum.',
                            'Het verloop van de dienst: welkom, sprekers, muziek, gebeden of gedichten.',
                            'Teksten van liederen die samen gezongen worden.',
                            'Een dankwoord en eventueel informatie over de condoleance of koffietafel.',
                        ],
                    ],
                    [
                        'title' => 'Welk formaat kies je?',
                        'text' => 'De meeste liturgieën zijn een A5-boekje: een A4-vel dubbelgevouwen en in het midden geniet. Een boekje heeft altijd een veelvoud van 4 pagina\'s, dus 4, 8, 12 of 16. Heeft je PDF bijvoorbeeld 10 pagina\'s, dan vullen wij aan het eind 2 lege pagina\'s aan. Wil je zelf bepalen waar de lege pagina\'s komen, voeg ze dan toe voordat je de PDF uploadt.',
                    ],
                    [
                        'title' => 'Zo bestel je een liturgie',
                        'ordered' => true,
                        'list' => [
                            'Maak de liturgie in Word, Canva of een ander programma en sla hem op als PDF, in het formaat van één pagina (meestal A5).',
                            'Upload de PDF op printmijnpdf.nl en kies "Magazine" (geniet boekje).',
                            'Vul het aantal exemplaren in. Online bestel je tot 100 stuks; heb je er meer nodig, mail of bel ons dan.',
                            'Kies afhalen (gratis, de volgende werkdag tussen 17:00 en 17:30) of bezorgen, en betaal met iDEAL.',
                        ],
                    ],
                    [
                        'title' => 'Controleer vóór het uploaden',
                        'text' => 'Bij haast sluipen er makkelijk fouten in. Laat iemand anders de namen, datums en liedteksten nog één keer nalezen. Staat er een foto of kleur tot aan de rand van de pagina? Dan vullen wij de rand automatisch aan, zodat er na het snijden geen wit randje ontstaat.',
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Hoe snel kan een liturgie klaar zijn?', 'answer' => 'Bestel je op een werkdag, dan kun je de boekjes de volgende werkdag tussen 17:00 en 17:30 gratis afhalen in Delfgauw. Laat je ze bezorgen, dan versturen we binnen 1 werkdag en bezorgt PostNL binnen 2 werkdagen. Is het nog sneller nodig? Bel 015-219 2525, dan kijken we wat er kan.'],
                ['question' => 'Hoeveel exemplaren kan ik bestellen?', 'answer' => 'Online bestel je 1 tot 100 exemplaren. Verwacht je meer gasten, mail dan naar info@printmijnpdf.nl of bel 015-219 2525, dan maken we het via de drukkerij.'],
                ['question' => 'Wat kost een liturgie?', 'answer' => 'Dat zie je direct na het uploaden van je PDF. De prijs bestaat uit € 10,00 startkosten, een prijs per pagina en het nieten. Afhalen is gratis.'],
                ['question' => 'Waar kan ik de liturgie afhalen?', 'answer' => 'Bij NIVO print | design | web, Exportweg 11, 2645 ED Delfgauw, naast Delft. Op werkdagen tussen 17:00 en 17:30. Je krijgt een mail zodra de boekjes klaarliggen.'],
            ],
            'slug' => 'liturgie-uitvaart-printen',
        ]);
    }

    /**
     * Ceremonie- of programmaboekje voor een bruiloft
     */
    public function bruiloft(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Boekje Bruiloft Printen | Liturgie en Programmaboekje | PrintMijnPDF',
                'description' => 'Ceremonie- of programmaboekje voor je bruiloft laten printen: full colour geniet boekje in A5 of A4, vanaf 1 exemplaar. Upload je PDF en zie direct de prijs.',
                'canonical' => route('landing.bruiloft'),
                'keywords' => 'boekje bruiloft printen, liturgie bruiloft drukken, programmaboekje bruiloft, ceremonieboekje printen, trouwboekje printen',
                'service_type' => 'Boekje bruiloft printen',
            ],
            'breadcrumb' => 'Boekje bruiloft printen',
            'hero' => [
                'title' => 'Boekje voor je bruiloft printen',
                'subtitle' => 'Liturgie, ceremonie- of programmaboekje in drukwerkkwaliteit',
                'cta' => 'Upload je boekje',
            ],
            'benefits' => [
                ['icon' => 'heart', 'title' => 'Mooi in full colour', 'text' => 'Jullie foto\'s en ontwerp haarscherp op wit papier.'],
                ['icon' => 'book', 'title' => 'Gevouwen en geniet', 'text' => 'Een net boekje in A5 of A4, van 4 tot 64 pagina\'s.'],
                ['icon' => 'repeat', 'title' => 'Eerst een proefexemplaar', 'text' => 'Bestel er één om te controleren, daarna de rest.'],
                ['icon' => 'euro', 'title' => 'Duidelijke prijs', 'text' => 'Je ziet de prijs direct na het uploaden, inclusief btw.'],
            ],
            'content' => [
                'intro' => 'Een boekje voor de ceremonie of het feest maakt je trouwdag persoonlijk: gasten lezen mee met de teksten, zingen de liedjes en weten wat er komt. Je ontwerpt het zelf, bijvoorbeeld in Canva of Word, en wij printen het als een net geniet boekje in drukwerkkwaliteit.',
                'sections' => [
                    [
                        'title' => 'Wat zet je in een bruiloftsboekje?',
                        'list' => [
                            'Jullie namen, de datum en een foto op de voorkant.',
                            'Het programma van de ceremonie: sprekers, muziek, gelofte en ringen.',
                            'Liedteksten, gedichten of lezingen om mee te lezen.',
                            'Praktische informatie voor de rest van de dag.',
                        ],
                    ],
                    [
                        'title' => 'Tips voor het ontwerp',
                        'list' => [
                            'Kies A5 als je een handzaam boekje wilt; dat is het meest gebruikte formaat.',
                            'Een boekje heeft altijd een veelvoud van 4 pagina\'s (4, 8, 12, 16…). Ontbreken er pagina\'s, dan vullen wij lege pagina\'s aan het eind aan.',
                            'Houd tekst minstens 5 mm van de rand. Op de pagina PDF aanleveren lees je hoe je achtergronden goed tot de rand laat doorlopen.',
                            'Sla je ontwerp op als PDF in het formaat van één pagina, niet als twee pagina\'s naast elkaar.',
                        ],
                    ],
                    [
                        'title' => 'Op tijd bestellen',
                        'text' => 'Bestel je boekjes een week of twee voor de bruiloft. Bestel eerst één exemplaar om kleuren en teksten te controleren. We versturen binnen 1 werkdag met PostNL, of je haalt het gratis af in Delfgauw bij Delft.',
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Hoeveel boekjes kan ik bestellen?', 'answer' => 'Online bestel je 1 tot 100 exemplaren. Heb je er meer nodig, mail dan naar info@printmijnpdf.nl.'],
                ['question' => 'Kan ik het boekje in Canva maken?', 'answer' => 'Ja. Kies in Canva bij downloaden "PDF voor drukwerk" en zet "Snijtekens en afloop" aan als je achtergrond tot de rand doorloopt. Upload daarna de PDF bij ons.'],
                ['question' => 'Op welk papier printen jullie?', 'answer' => 'Op hoogwaardig wit papier van professionele drukwerkkwaliteit, met heldere kleuren voor foto\'s en tekst.'],
                ['question' => 'Wanneer moet ik bestellen?', 'answer' => 'Liefst een week of twee van tevoren, zodat je eerst een proefexemplaar kunt bekijken. Haast? We versturen binnen 1 werkdag en afhalen kan de volgende werkdag.'],
            ],
            'slug' => 'boekje-bruiloft-printen',
        ]);
    }

    /**
     * Printen zonder eigen printer: eerlijk over wanneer het loont
     */
    public function zonderPrinter(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Printen Zonder Printer | PDF Online Laten Printen | PrintMijnPDF',
                'description' => 'Geen printer thuis? Upload je PDF en laat hem printen in full colour, als boekje of losse pagina\'s. Binnen 1 werkdag verzonden of gratis afhalen bij Delft.',
                'canonical' => route('landing.zonder-printer'),
                'keywords' => 'printen zonder printer, online printen, document laten printen, pdf online printen, waar kan ik printen',
                'service_type' => 'Online printservice',
            ],
            'breadcrumb' => 'Printen zonder printer',
            'hero' => [
                'title' => 'Printen zonder printer',
                'subtitle' => 'Upload je PDF, wij printen hem en sturen hem op',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'file-text', 'title' => 'Geen printer nodig', 'text' => 'Geen inkt, geen vastgelopen papier, geen gedoe.'],
                ['icon' => 'palette', 'title' => 'Beter dan thuis', 'text' => 'Full colour in drukwerkkwaliteit, van een echte drukkerij.'],
                ['icon' => 'package', 'title' => 'Thuisbezorgd', 'text' => 'Binnen 1 werkdag verzonden, PostNL bezorgt binnen 2 werkdagen.'],
                ['icon' => 'euro', 'title' => 'Vanaf € 0,15 per pagina', 'text' => 'Plus € 10,00 startkosten per bestelling. Geen account nodig.'],
            ],
            'content' => [
                'intro' => 'Steeds minder mensen hebben een printer thuis. Moet je toch iets op papier hebben, zoals een reader, een handleiding, een verslag of een boekje? Upload je PDF bij PrintMijnPDF, kies of je losse pagina\'s of een geniet boekje wilt, en wij printen het in drukwerkkwaliteit.',
                'sections' => [
                    [
                        'title' => 'Wanneer is dit handig?',
                        'text' => [
                            'Online laten printen loont vooral bij documenten van meerdere pagina\'s, bij kleur, en als je het netjes wilt hebben, bijvoorbeeld als boekje. Je hebt dan geen gedoe met inkt en papier en het resultaat is mooier dan van een thuisprinter.',
                            'Heb je maar één of twee zwart-witpagina\'s nodig, bijvoorbeeld een formulier? Dan is de bibliotheek of een copyshop in de buurt goedkoper, omdat wij per bestelling € 10,00 startkosten rekenen.',
                        ],
                    ],
                    [
                        'title' => 'Geen PDF maar een Word-bestand?',
                        'list' => [
                            'Word: kies Bestand › Opslaan als › PDF.',
                            'Google Docs: kies Bestand › Downloaden › PDF-document.',
                            'Pages (Mac): kies Archief › Exporteer naar › PDF.',
                            'Canva: kies Delen › Downloaden › PDF voor drukwerk.',
                        ],
                    ],
                    [
                        'title' => 'Zo werkt het',
                        'ordered' => true,
                        'list' => [
                            'Upload je PDF op printmijnpdf.nl; je ziet direct het aantal pagina\'s en de prijs.',
                            'Kies losse pagina\'s (enkel- of dubbelzijdig) of een geniet boekje.',
                            'Kies bezorgen met PostNL of gratis afhalen in Delfgauw.',
                            'Betaal met iDEAL. Binnen 1 werkdag gaat je pakket op de post.',
                        ],
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Waar kan ik printen als ik geen printer heb?', 'answer' => 'Bij een bibliotheek, copyshop of online printservice zoals PrintMijnPDF. Online is handig voor documenten van meerdere pagina\'s of in kleur: je uploadt je PDF en krijgt het geprint thuisbezorgd.'],
                ['question' => 'Moet ik een account aanmaken?', 'answer' => 'Nee. Je uploadt je PDF, vult je adres in en betaalt met iDEAL.'],
                ['question' => 'Kan ik ook zwart-wit printen?', 'answer' => 'We printen standaard in full colour. Een document in zwart-wit wordt gewoon zwart-wit geprint, tegen dezelfde prijs.'],
                ['question' => 'Wat kost het?', 'answer' => 'Je betaalt € 10,00 startkosten per bestelling plus € 0,15 per A4-pagina of € 0,10 per A5-pagina. Bezorgen kost € 7,50, afhalen is gratis. Na het uploaden zie je meteen de totaalprijs.'],
            ],
            'slug' => 'printen-zonder-printer',
        ]);
    }

    /**
     * Regio Delft, Den Haag, Zoetermeer, Rotterdam: afhalen in Delfgauw
     */
    public function delft(): View
    {
        return $this->page([
            'meta' => [
                'title' => 'Printen in Delft | Morgen Gratis Afhalen | PrintMijnPDF',
                'description' => 'PDF of boekje printen in de regio Delft, Den Haag, Zoetermeer of Rotterdam? Bestel online en haal het de volgende werkdag gratis af bij onze drukkerij in Delfgauw.',
                'canonical' => route('landing.delft'),
                'keywords' => 'printen delft, pdf printen delft, boekje printen den haag, printen zoetermeer, drukkerij delft, printshop delft',
                'service_type' => 'PDF printen regio Delft',
            ],
            'breadcrumb' => 'Printen in Delft en omgeving',
            'hero' => [
                'title' => 'Printen in Delft en omgeving',
                'subtitle' => 'Online bestellen, morgen gratis afhalen in Delfgauw',
                'cta' => 'Upload je PDF',
            ],
            'benefits' => [
                ['icon' => 'clock', 'title' => 'Morgen afhalen', 'text' => 'Vanaf de volgende werkdag, tussen 17:00 en 17:30.'],
                ['icon' => 'euro', 'title' => 'Afhalen is gratis', 'text' => 'Geen verzendkosten en geen spoedtoeslag.'],
                ['icon' => 'briefcase', 'title' => 'Echte drukkerij', 'text' => 'NIVO print | design | web, sinds 1980 in de regio.'],
                ['icon' => 'phone', 'title' => 'Vandaag nodig?', 'text' => 'Bel 015-219 2525, dan kijken we wat er kan.'],
            ],
            'content' => [
                'intro' => 'PrintMijnPDF is de online printservice van drukkerij NIVO print | design | web in Delfgauw, gemeente Pijnacker-Nootdorp, direct naast Delft. Woon of werk je in Delft, Den Haag, Rijswijk, Zoetermeer, Pijnacker, Nootdorp, het Westland of Rotterdam? Dan bestel je online en haal je je drukwerk de volgende werkdag gratis op.',
                'sections' => [
                    [
                        'title' => 'Afhaaladres',
                        'list' => [
                            'NIVO print | design | web, Exportweg 11, 2645 ED Delfgauw.',
                            'Afhalen op werkdagen tussen 17:00 en 17:30, vanaf de werkdag na je bestelling.',
                            'Je krijgt een mail zodra je bestelling klaarligt.',
                        ],
                    ],
                    [
                        'title' => 'Wat kun je laten printen?',
                        'text' => 'Geniet boekjes van 4 tot 64 pagina\'s en losse pagina\'s, full colour, in elk formaat tot en met A3. Handig voor studenten in Delft en Den Haag met een scriptie of reader, voor verenigingen en kerken met een programmaboekje, en voor bedrijven met handleidingen of cursusmateriaal.',
                    ],
                    [
                        'title' => 'Liever bezorgd?',
                        'text' => 'Dat kan in heel Nederland. We versturen binnen 1 werkdag met PostNL en PostNL bezorgt binnen 2 werkdagen. Verzending kost € 7,50 per bestelling.',
                    ],
                ],
            ],
            'faq' => [
                ['question' => 'Waar kan ik in Delft een PDF laten printen?', 'answer' => 'Bestel online bij PrintMijnPDF en haal je drukwerk de volgende werkdag gratis op bij drukkerij NIVO print | design | web, Exportweg 11 in Delfgauw, vlak bij Delft. Afhalen kan op werkdagen tussen 17:00 en 17:30.'],
                ['question' => 'Kan ik mijn drukwerk dezelfde dag ophalen?', 'answer' => 'Standaard haal je het de volgende werkdag op. Heb je het dezelfde dag nodig, bel dan voor je bestelt naar 015-219 2525, dan kijken we wat er mogelijk is.'],
                ['question' => 'Kan ik ook langskomen zonder online te bestellen?', 'answer' => 'Bestellen gaat via de website, zodat je bestand direct gecontroleerd wordt en de prijs vastligt. Daarna haal je het op in Delfgauw.'],
                ['question' => 'Vanuit welke plaatsen haal je het makkelijk op?', 'answer' => 'Delfgauw ligt naast Delft, Pijnacker en Nootdorp, en op korte afstand van Den Haag, Rijswijk, Zoetermeer en Rotterdam.'],
            ],
            'slug' => 'printen-delft',
        ]);
    }
}
