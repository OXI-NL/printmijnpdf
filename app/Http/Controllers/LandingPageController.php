<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Scriptie printen - voor studenten HBO/WO
     */
    public function scriptie(): View
    {
        return view('landing.page', [
            'meta' => [
                'title' => 'Scriptie Printen en Inbinden | Binnen 3 Dagen | PrintMijnPDF',
                'description' => 'Laat je scriptie professioneel printen als geniet boekje. Full colour drukwerkkwaliteit, binnen 3 werkdagen klaar. Ideaal voor HBO en WO afstudeerders.',
                'canonical' => route('landing.scriptie'),
                'keywords' => 'scriptie printen, scriptie inbinden, afstudeerscriptie drukken, scriptie laten printen',
            ],
            'hero' => [
                'title' => 'Scriptie printen en inbinden',
                'subtitle' => 'Jouw afstudeerwerk verdient drukwerkkwaliteit',
                'cta' => 'Upload je scriptie',
            ],
            'benefits' => [
                [
                    'icon' => 'clock',
                    'title' => 'Binnen 3 werkdagen klaar',
                    'text' => 'Bestel voor 11:00, binnen 3 werkdagen in huis of gratis afhalen.',
                ],
                [
                    'icon' => 'palette',
                    'title' => 'Full colour drukwerk',
                    'text' => 'Alle grafieken, tabellen en afbeeldingen in volle kleur.',
                ],
                [
                    'icon' => 'book',
                    'title' => 'Professioneel geniet',
                    'text' => 'Nette afwerking als boekje, geen goedkope ringband.',
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
                        'text' => 'Wij zijn geen online print-app, maar een echte drukkerij. NIVO Druk & Multimedia bestaat al sinds 1985 en print dagelijks voor bedrijven, overheden en onderwijsinstellingen. Die kwaliteit krijg jij nu ook voor je scriptie — tegen een studentvriendelijke prijs.',
                    ],
                    [
                        'title' => 'Hoe werkt het?',
                        'text' => 'Upload je PDF, kies voor een geniet boekje, vul je gegevens in en reken af met iDEAL. Binnen 3 werkdagen heb je je scripties in huis. Heb je haast? Bel ons, dan kijken we wat mogelijk is.',
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
                    'answer' => 'Voor een geniet boekje geldt een maximum van 64 pagina\'s. Heb je een langere scriptie? Neem contact op, dan bespreken we de opties zoals ringband of lijmbinding.',
                ],
                [
                    'question' => 'Welk formaat moet mijn PDF zijn?',
                    'answer' => 'Wij ondersteunen A4 en A5 formaat. De meeste scripties zijn A4. Zorg dat je PDF in het juiste formaat is opgeslagen.',
                ],
                [
                    'question' => 'Kan ik een proefdruk bestellen?',
                    'answer' => 'Ja, bestel gewoon 1 exemplaar om te controleren of alles goed is. Ben je tevreden? Bestel dan de rest.',
                ],
                [
                    'question' => 'Wordt dubbelzijdig geprint?',
                    'answer' => 'Ja, boekjes worden standaard dubbelzijdig geprint. Zo bespaar je papier en krijg je een compacter resultaat.',
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
        return view('landing.page', [
            'meta' => [
                'title' => 'Reader Printen | Studiehandleiding Drukken | PrintMijnPDF',
                'description' => 'Laat je reader of studiehandleiding printen in full colour. Binnen 3 werkdagen klaar, vanaf €0,15 per pagina. Echte drukwerkkwaliteit.',
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
                    'title' => 'Binnen 3 werkdagen',
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
        return view('landing.page', [
            'meta' => [
                'title' => 'Cursusmateriaal Printen | Trainingsmateriaal Drukken | PrintMijnPDF',
                'description' => 'Print je cursusmateriaal of trainingshandleiding professioneel. Full colour, binnen 3 werkdagen. Ideaal voor trainers, coaches en docenten.',
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
                    'text' => 'Binnen 3 werkdagen, ook voor last-minute trainingen.',
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
        return view('landing.page', [
            'meta' => [
                'title' => 'Eigen Boekje Maken en Printen | PDF naar Boekje | PrintMijnPDF',
                'description' => 'Maak je eigen boekje van een PDF. Receptenboekje, fotoboekje, verhalen bundelen. Full colour geprint, binnen 3 dagen in huis.',
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
        return view('landing.page', [
            'meta' => [
                'title' => 'Handleiding Printen | Instructieboekje Drukken | PrintMijnPDF',
                'description' => 'Print je handleiding of instructieboekje professioneel. Technische documentatie in drukwerkkwaliteit. Binnen 3 werkdagen.',
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
                    'answer' => 'We printen A4 en A5. A5 is handig voor compacte handleidingen bij producten.',
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
        return view('landing.page', [
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
                    'text' => 'Binnen 3 werkdagen. Spoed mogelijk in overleg.',
                ],
                [
                    'icon' => 'shield',
                    'title' => 'Betrouwbare partner',
                    'text' => 'Onderdeel van NIVO Druk & Multimedia, 35+ jaar ervaring.',
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
                        'text' => 'Voor uitgebreider drukwerk (brochures, flyers, gebonden boeken) kun je terecht bij onze moederorganisatie NIVO. Wij verbinden je graag door.',
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
}
