<?php

return [
    /*
    | IndexNow-sleutel (geen geheim): hetzelfde bestand staat als
    | public/{key}.txt, zodat Bing kan controleren dat de melding van ons komt.
    | Bing voedt de zoekfunctie van ChatGPT en Copilot.
    */
    'indexnow_key' => env('INDEXNOW_KEY', '20c98bcbfac88b239eec811a988ab9ab'),
];
