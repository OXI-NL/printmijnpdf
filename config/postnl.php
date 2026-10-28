<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PostNL Shipping API (verzendlabels vanuit de admin)
    |--------------------------------------------------------------------------
    |
    | Zonder API-sleutel blijft alleen de handmatige Track & Trace-invoer over.
    | Eerst testen met de sandbox-sleutel; live pas na goedkeuring door PostNL.
    |
    */

    'api_key' => env('POSTNL_API_KEY'),
    'sandbox' => env('POSTNL_SANDBOX', true),
    'customer_number' => env('POSTNL_CUSTOMER_NUMBER', '9121183'),
    'customer_code' => env('POSTNL_CUSTOMER_CODE', 'HCBM'),

    // Afzender op het label
    'sender' => [
        'company_name' => 'PrintMijnPDF',
        'street' => 'Exportweg',
        'house_number' => '11',
        'postcode' => '2645ED',
        'city' => 'Delfgauw',
    ],

    // Verzendvormen in het verzendvenster; de eerste is de standaard.
    // Brievenbuspakje+ 48 uur past bij "PostNL bezorgt binnen 2 werkdagen" op de site.
    'products' => [
        'brievenbuspakje' => ['code' => '2948', 'label' => 'Brievenbuspakje 48 uur', 'weight' => 500],
        'brievenbuspakje_24' => ['code' => '2928', 'label' => 'Brievenbuspakje 24 uur', 'weight' => 500],
        'pakket' => ['code' => '3085', 'label' => 'Pakket', 'weight' => 1000],
    ],
];
