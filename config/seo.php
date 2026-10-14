<?php

return [
    /*
    | IndexNow-sleutel (geen geheim): hetzelfde bestand staat als
    | public/{key}.txt, zodat Bing kan controleren dat de melding van ons komt.
    | Bing voedt de zoekfunctie van ChatGPT en Copilot.
    */
    // Vast domein voor commando's: op de command line is er geen request en
    // zou Laravel APP_URL gebruiken, die op de server niet het live domein is.
    'site_url' => env('SEO_SITE_URL', 'https://printmijnpdf.nl'),

    // Profielen elders (Google Bedrijfsprofiel, LinkedIn, KvK…): vul aan zodra ze
    // bestaan. Zoekmachines en AI-assistenten koppelen het merk zo aan die bronnen.
    'same_as' => [
        'https://maps.google.com/?cid=8050172775051229699', // Google Bedrijfsprofiel
        'https://nivo.com',
    ],

    'google_maps' => 'https://maps.google.com/?cid=8050172775051229699',

    'indexnow_key' => env('INDEXNOW_KEY', '20c98bcbfac88b239eec811a988ab9ab'),
];
