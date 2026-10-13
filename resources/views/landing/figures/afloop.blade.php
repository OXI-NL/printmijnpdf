{{-- Afloop, snijlijn, veilige marge en snijtekens; schematisch, niet op schaal --}}
<figure class="content-figure">
    <svg viewBox="0 0 580 300" role="img" aria-labelledby="figAfloopTitle" xmlns="http://www.w3.org/2000/svg">
        <title id="figAfloopTitle">Een pagina met 3 mm afloop rondom, de snijlijn, een veilige marge voor tekst en snijtekens in de hoeken</title>
        {{-- Achtergrond loopt door tot in de afloop --}}
        <rect x="50" y="30" width="180" height="240" fill="#f8c9cd"/>
        {{-- Pagina na het snijden --}}
        <rect x="62" y="42" width="156" height="216" fill="#fbe3e5" stroke="#262626" stroke-width="1.5"/>
        {{-- Veilige marge --}}
        <rect x="80" y="60" width="120" height="180" fill="#ffffff" stroke="#e63946" stroke-width="1.5" stroke-dasharray="5 4"/>
        <g fill="#a3a3a3">
            <rect x="92" y="76" width="80" height="8" rx="2" fill="#525252"/>
            <rect x="92" y="96" width="96" height="5" rx="2"/>
            <rect x="92" y="108" width="90" height="5" rx="2"/>
            <rect x="92" y="120" width="96" height="5" rx="2"/>
            <rect x="92" y="132" width="70" height="5" rx="2"/>
            <rect x="92" y="150" width="96" height="60" rx="3" fill="#d4d4d4"/>
            <rect x="92" y="220" width="84" height="5" rx="2"/>
        </g>
        {{-- Snijtekens: in het verlengde van de snijlijn, buiten de afloop --}}
        <g stroke="#262626" stroke-width="1.2">
            <line x1="62" y1="6" x2="62" y2="24"/><line x1="26" y1="42" x2="44" y2="42"/>
            <line x1="218" y1="6" x2="218" y2="24"/><line x1="236" y1="42" x2="254" y2="42"/>
            <line x1="62" y1="276" x2="62" y2="294"/><line x1="26" y1="258" x2="44" y2="258"/>
            <line x1="218" y1="276" x2="218" y2="294"/><line x1="236" y1="258" x2="254" y2="258"/>
        </g>
        {{-- Legenda --}}
        <g font-family="inherit" font-size="13" fill="#262626">
            <rect x="290" y="44" width="18" height="14" fill="#f8c9cd"/>
            <text x="318" y="56">Afloop (3 mm): wordt afgesneden</text>
            <line x1="290" y1="90" x2="308" y2="90" stroke="#262626" stroke-width="1.5"/>
            <text x="318" y="94">Snijlijn: formaat van je pagina</text>
            <line x1="290" y1="128" x2="308" y2="128" stroke="#e63946" stroke-width="1.5" stroke-dasharray="5 4"/>
            <text x="318" y="132">Veilige marge: tekst blijft hierbinnen</text>
            <text x="318" y="150" fill="#525252" font-size="12">5 mm met afloop, 8 mm zonder</text>
            <line x1="290" y1="184" x2="290" y2="198" stroke="#262626" stroke-width="1.2"/>
            <line x1="294" y1="194" x2="308" y2="194" stroke="#262626" stroke-width="1.2"/>
            <text x="318" y="196">Snijtekens: waar gesneden wordt</text>
        </g>
    </svg>
</figure>
