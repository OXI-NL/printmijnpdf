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
    'company_address' => env('INVOICE_COMPANY_ADDRESS', 'Straatnaam 1'),
    'company_postcode' => env('INVOICE_COMPANY_POSTCODE', '1234 AB'),
    'company_city' => env('INVOICE_COMPANY_CITY', 'Amsterdam'),
    'company_country' => env('INVOICE_COMPANY_COUNTRY', 'Nederland'),
    'company_email' => env('INVOICE_COMPANY_EMAIL', 'info@printmijnpdf.nl'),
    'company_phone' => env('INVOICE_COMPANY_PHONE', ''),
    'company_website' => env('INVOICE_COMPANY_WEBSITE', 'www.printmijnpdf.nl'),

    // KvK en BTW (verplicht op facturen)
    'kvk_number' => env('INVOICE_KVK_NUMBER', '12345678'),
    'btw_id' => env('INVOICE_BTW_ID', 'NL123456789B01'),

    // IBAN (optioneel op factuur)
    'iban' => env('INVOICE_IBAN', ''),

    // BTW percentage (standaardtarief voor printservices)
    'btw_percentage' => 21,

    // Factuurnummer prefix (PMF-YYYY-0001)
    'invoice_prefix' => env('INVOICE_PREFIX', 'PMF'),
];
