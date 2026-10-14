@extends('legal.layout')

@section('title', 'Leveringsvoorwaarden')
@section('description', 'Op bestellingen bij PrintMijnPDF zijn de leveringsvoorwaarden van de grafimediabranche (KVGO) van toepassing.')

@section('content')
    @php
        // De KVGO-voorwaarden staan als PDF in public/
        $pdf = 'Leveringsvoorwaarden-Gedeponeerd-2-juli-2019-KVGO-website-gecomprimeerde-versie.pdf';
        $hasPdf = file_exists(public_path($pdf));
    @endphp

    <h1>Leveringsvoorwaarden</h1>
    <p class="lead">PrintMijnPDF is onderdeel van NIVO Druk & Multimedia B.V., lid van KVGO, de brancheorganisatie voor de grafimedia. Op al onze bestellingen zijn daarom de Leveringsvoorwaarden voor de grafimediabranche van KVGO van toepassing.</p>

    @if ($hasPdf)
        <p><a href="{{ asset($pdf) }}" target="_blank" rel="noopener">Lees de leveringsvoorwaarden (PDF)</a>, gedeponeerd bij de rechtbank op 2 juli 2019.</p>
    @else
        <p>Wil je de volledige voorwaarden lezen? Mail naar <a href="mailto:info@printmijnpdf.nl">info@printmijnpdf.nl</a>, dan sturen we ze je kosteloos toe.</p>
    @endif

    <h2>In het kort</h2>
    <ul>
        <li>We versturen je bestelling binnen 1 werkdag met PostNL. Daarna bezorgt PostNL binnen 2 werkdagen; op de bezorging zelf hebben wij geen invloed.</li>
        <li>Afhalen kan gratis bij NIVO in Delfgauw, elke werkdag tussen 17:00 en 17:30.</li>
        <li>Is je bestelling nog niet geprint? Dan kun je kosteloos annuleren via <a href="mailto:info@printmijnpdf.nl">info@printmijnpdf.nl</a>.</li>
        <li>Niet tevreden over de printkwaliteit? Neem contact met ons op, dan lossen we het op.</li>
    </ul>
    <p>Bij verschil tussen deze samenvatting en de leveringsvoorwaarden gelden de leveringsvoorwaarden.</p>
@endsection
