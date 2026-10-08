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

    'indexnow_key' => env('INDEXNOW_KEY', '20c98bcbfac88b239eec811a988ab9ab'),
];
