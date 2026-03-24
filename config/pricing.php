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

    // Vaste kosten
    'startup' => env('PRICE_STARTUP', 1000),        // €10.00
    'startup_extra' => env('PRICE_STARTUP_EXTRA', 250), // €2.50 per extra exemplaar
    'binding' => env('PRICE_BINDING', 750),         // €7.50
    'shipping' => env('PRICE_SHIPPING', 675),       // €6.75
];
