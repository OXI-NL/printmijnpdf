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
    'per_page_a4' => env('PRICE_PER_PAGE_A4', 10),  // €0.10
    'per_page_a5' => env('PRICE_PER_PAGE_A5', 7),   // €0.07

    // Vaste kosten
    'startup' => env('PRICE_STARTUP', 1000),        // €10.00
    'binding' => env('PRICE_BINDING', 500),         // €5.00
    'shipping' => env('PRICE_SHIPPING', 675),       // €6.75
];
