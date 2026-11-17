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
    // Grote oplage: boven dit aantal exemplaren geldt het hogere verzendtarief
    'shipping_large_above' => 100,
    'shipping_large' => env('PRICE_SHIPPING_LARGE', 1250), // €12.50

    // Grootste oplage die je zelf online bestelt; daarboven via contact
    'max_quantity' => 100,        // boekjes
    'max_quantity_loose' => 500,  // losse pagina's
    // Grootste bestelling in vellen, omgerekend naar A4 (A5 telt half, A3 dubbel):
    // ongeveer wat in één doos past. Eén exemplaar kan altijd.
    'max_sheets_a4' => 4500,

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
