<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prijzen (in centen)
    |--------------------------------------------------------------------------
    |
    | Alle prijzen zijn in eurocenten om afrondingsproblemen te voorkomen.
    |
    */

    // Prijs per pagina
    'per_page_a4' => env('PRICE_PER_PAGE_A4', 15),  // €0.15
    'per_page_a5' => env('PRICE_PER_PAGE_A5', 10),  // €0.10
    'per_page_a3' => env('PRICE_PER_PAGE_A3', 20),  // €0.20 (alleen losse pagina's)

    // Vaste kosten
    'startup' => env('PRICE_STARTUP', 1000),        // €10.00
    'binding' => env('PRICE_BINDING', 500),         // €5.00 eerste boekje
    'binding_extra' => env('PRICE_BINDING_EXTRA', 250), // €2.50 per extra boekje
    'shipping' => env('PRICE_SHIPPING', 750),       // €7.50

    // Grootste oplage die je zelf online bestelt; daarboven via contact
    'max_quantity' => 100,

    // Actieve promotiecodes
    'promo_codes' => [
        'PMPWINTER' => [ // Eindejaarsactie
            'discount_percent' => 15,
            'applies_to' => ['pages', 'startup', 'binding'], // afdruk, startkosten, afwerking
            'active' => true,
        ],
        'PMPNIEUW' => [ // Introductieaanbieding (27 maart t/m 27 oktober 2026)
            'discount_percent' => 25,
            'applies_to' => ['pages', 'startup', 'binding'],
            'active' => false,
        ],
    ],
];
