{{--
    Koopblokken direct onder de hero. Elk blok verschijnt alleen als de pagina
    de gegevens meegeeft. Prijzen komen uit Order::calculatePrice (zie
    LandingPageController::priceBreakdown/quantityComparison), nooit uit tekst.
--}}
@if(!empty($conditions))
<section class="kh kh-conditions" aria-labelledby="kh-conditions-title">
    <div class="container">
        <div class="kh-card">
            <h2 id="kh-conditions-title">{{ $conditions['title'] }}</h2>
            <dl class="kh-dl">
                @foreach($conditions['items'] as [$term, $text])
                <div><dt>{{ $term }}</dt><dd>{{ $text }}</dd></div>
                @endforeach
            </dl>
            @if(!empty($conditions['note']))
            <p class="kh-note">{{ $conditions['note'] }}</p>
            @endif
        </div>
    </div>
</section>
@endif

@if(!empty($usecases))
<section class="kh kh-usecases" aria-labelledby="kh-usecases-title">
    <div class="container">
        <h2 id="kh-usecases-title">{{ $usecases['title'] }}</h2>
        <div class="kh-usecase-grid">
            @foreach($usecases['items'] as $usecase)
            <article class="kh-usecase">
                @include('landing.figures.boekje-voorbeeld', ['variant' => $usecase['variant']])
                <h3>{{ $usecase['title'] }}</h3>
                <p>{{ $usecase['text'] }}</p>
            </article>
            @endforeach
        </div>
        @if(!empty($usecases['note']))
        <p class="kh-note">{{ $usecases['note'] }}</p>
        @endif
    </div>
</section>
@endif

@if(!empty($priceExample) || !empty($quantities))
<section class="kh kh-price" aria-labelledby="kh-price-title">
    <div class="container">
        <h2 id="kh-price-title">Wat betaal je in totaal?</h2>
        <div class="kh-price-grid">
            @if(!empty($priceExample))
            <div class="kh-card">
                <h3>{{ $priceExample['label'] }}</h3>
                <table class="kh-table">
                    <tbody>
                        @foreach($priceExample['lines'] as [$what, $amount])
                        <tr><td>{{ $what }}</td><td>{{ $amount }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th scope="row">Totaal, inclusief btw</th><td>{{ $priceExample['total'] }}</td></tr>
                    </tfoot>
                </table>
                <p class="kh-pickup">Zelf afhalen in Delfgauw: <strong>{{ $priceExample['pickupTotal'] }}</strong> (geen verzendkosten).</p>
            </div>
            @endif

            @if(!empty($quantities))
            <div class="kh-card">
                <h3>Meer exemplaren: {{ $quantities['label'] }}</h3>
                @if(!empty($quantitiesIntro))
                <p class="kh-intro">{{ $quantitiesIntro }}</p>
                @endif
                <table class="kh-table kh-qty">
                    <thead>
                        <tr><th scope="col">Exemplaren</th><th scope="col">Totaal</th><th scope="col">Per exemplaar</th></tr>
                    </thead>
                    <tbody>
                        @foreach($quantities['rows'] as $row)
                        <tr><td>{{ $row['quantity'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['each'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="kh-note">Inclusief btw, startkosten en verzending. Afhalen is gratis.</p>
            </div>
            @endif
        </div>
        <p class="kh-note kh-center">Voorbeelden zonder kortingscode. Na het uploaden zie je de prijs van jouw PDF.</p>
        <div class="kh-center">
            <a href="{{ url('/') }}#upload" class="hero-cta" data-cta="prijs">{{ $hero['cta'] }} &rarr;</a>
        </div>
    </div>
</section>
@endif

@if(!empty($choice))
<section class="kh kh-choice" aria-labelledby="kh-choice-title">
    <div class="container">
        <h2 id="kh-choice-title">Geniet boekje of losse prints?</h2>
        <div class="kh-choice-grid">
            <div class="kh-card">
                <h3>Geniet boekje</h3>
                <ul>
                    <li>4 tot 64 pagina's, gevouwen en in het midden geniet.</li>
                    <li>Altijd dubbelzijdig: elk gevouwen vel geeft 4 pagina's.</li>
                    <li>Telt je PDF geen veelvoud van 4? Dan vullen we aan met blanco pagina's aan het eind.</li>
                    <li>A5, A4 of een eigen formaat tot ongeveer A4.</li>
                </ul>
            </div>
            <div class="kh-card">
                <h3>Losse prints</h3>
                <ul>
                    <li>Elk aantal pagina's, ook meer dan 64.</li>
                    <li><strong>Dubbelzijdig:</strong> twee pagina's op de voor- en achterkant van één vel.</li>
                    <li><strong>Enkelzijdig:</strong> elke pagina op een eigen vel, de achterkant blijft wit.</li>
                    <li>Formaten tot en met A3. Niet geniet of gebonden.</li>
                </ul>
            </div>
        </div>
        <p class="kh-note kh-center">Je betaalt per pagina van je PDF; enkel- of dubbelzijdig kost hetzelfde.</p>
    </div>
</section>
@endif
