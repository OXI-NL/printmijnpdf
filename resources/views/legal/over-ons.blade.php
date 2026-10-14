@extends('legal.layout')

@section('title', 'Over ons: de drukkerij achter PrintMijnPDF')
@section('description', 'PrintMijnPDF is de online printservice van NIVO Druk & Multimedia, een drukkerij in Delfgauw (bij Delft) sinds 1980. Echte drukwerkkwaliteit, vanaf 1 exemplaar.')

@section('schema')
    @php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            'url' => route('over-ons'),
            'name' => 'Over PrintMijnPDF',
            'about' => [
                '@type' => 'Organization',
                'name' => 'PrintMijnPDF',
                'url' => url('/'),
                'logo' => url('favicon.png'),
                'email' => 'info@printmijnpdf.nl',
                'telephone' => '+31152192525',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Exportweg 11',
                    'postalCode' => '2645 ED',
                    'addressLocality' => 'Delfgauw',
                    'addressCountry' => 'NL',
                ],
                'parentOrganization' => [
                    '@type' => 'Organization',
                    'name' => 'NIVO Druk & Multimedia B.V.',
                    'url' => 'https://nivo.com',
                    'foundingDate' => '1980',
                ],
                'sameAs' => config('seo.same_as', []),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection

@section('content')
    <h1>Over PrintMijnPDF</h1>
    <p class="lead">PrintMijnPDF is geen print-app maar een echte drukkerij. Achter de website zit NIVO Druk & Multimedia, sinds 1980 gevestigd in Delfgauw, vlak bij Delft.</p>

    <h2>Waarom we PrintMijnPDF zijn begonnen</h2>
    <p>Bij een drukkerij denk je al snel aan grote oplages en offertes. Maar veel mensen hebben juist een paar exemplaren nodig: een scriptie, een reader, een liturgie of een handleiding. Met PrintMijnPDF bestel je die in een paar minuten, vanaf 1 exemplaar, met dezelfde drukwerkkwaliteit als onze zakelijke klanten krijgen.</p>

    <h2>Wat we met je PDF doen</h2>
    <ul>
        <li>We herkennen het formaat en het aantal pagina's van je PDF en laten direct de prijs zien.</li>
        <li>Loopt je ontwerp tot de rand? Dan vullen we de rand aan, zodat er na het snijden geen wit randje ontstaat.</li>
        <li>Voor een boekje zetten we de pagina's zelf in de juiste volgorde op de vellen. Jij levert gewoon een PDF aan.</li>
        <li>Voor het printen controleren we je bestand nog een keer. Daarna printen, vouwen, nieten en snijden we het in onze drukkerij.</li>
    </ul>

    <h2>Snel en dichtbij</h2>
    <p>We versturen je bestelling binnen 1 werkdag met PostNL, dat binnen 2 werkdagen bezorgt. Woon je in de buurt? Dan haal je het de volgende werkdag gratis op aan de Exportweg 11 in Delfgauw, tussen 17:00 en 17:30.</p>

    <h2>Lid van KVGO</h2>
    <p>NIVO is lid van KVGO, de brancheorganisatie voor de grafimedia. Op onze bestellingen zijn de <a href="{{ route('leveringsvoorwaarden') }}">leveringsvoorwaarden van de grafimediabranche</a> van toepassing.</p>

    <h2>Contact</h2>
    <p>NIVO Druk & Multimedia B.V.<br>
        Exportweg 11, 2645 ED Delfgauw<br>
        <a href="mailto:info@printmijnpdf.nl">info@printmijnpdf.nl</a> · <a href="tel:0152192525">015-219 2525</a><br>
        <a href="https://nivo.com" target="_blank" rel="noopener">nivo.com</a></p>

    <a class="cta" href="{{ url('/') }}">Upload je PDF</a>
@endsection
