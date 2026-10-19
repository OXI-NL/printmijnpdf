<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bedrijfsgegevens voor facturen
    |--------------------------------------------------------------------------
    |
    | Deze gegevens worden afgedrukt op elke factuur.
    | Pas aan via .env of direct in dit bestand.
    |
    */

    'company_name' => env('INVOICE_COMPANY_NAME', 'PrintMijnPDF'),
    // Rechtspersoon achter de handelsnaam PrintMijnPDF
    'legal_name' => 'NIVO Druk & Multimedia B.V.',
    'company_address' => env('INVOICE_COMPANY_ADDRESS', 'Exportweg 11'),
    'company_postcode' => env('INVOICE_COMPANY_POSTCODE', '2645 ED'),
    'company_city' => env('INVOICE_COMPANY_CITY', 'Delfgauw'),
    'company_country' => env('INVOICE_COMPANY_COUNTRY', 'Nederland'),
    'company_email' => env('INVOICE_COMPANY_EMAIL', 'info@printmijnpdf.nl'),
    'company_phone' => env('INVOICE_COMPANY_PHONE', '015-219 2525'),
    'company_website' => env('INVOICE_COMPANY_WEBSITE', 'www.printmijnpdf.nl'),

    // KvK en BTW-id van NIVO Druk & Multimedia B.V. (verplicht op facturen,
    // website en mail). Bewust niet via .env: dit zijn vaste gegevens, en een
    // oude .env-waarde mag ze niet stil overschrijven.
    'kvk_number' => '68331177',
    'btw_id' => 'NL857396183B01',

    // IBAN (optioneel op factuur)
    'iban' => env('INVOICE_IBAN', ''),

    // BTW percentage (standaardtarief voor printservices)
    'btw_percentage' => 21,

    // Factuurnummer prefix (PMF-YYYY-0001)
    'invoice_prefix' => env('INVOICE_PREFIX', 'PMF'),
];
