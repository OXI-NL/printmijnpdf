<?php

/*
 * Google Analytics 4. Definities van alle events staan in docs/analytics-meting.md.
 */
return [

    // Meet-ID van de webstream van printmijnpdf.nl. Leeg = geen GA4 laden.
    'ga4_measurement_id' => env('GA4_MEASUREMENT_ID', 'G-9F48GD4CX5'),

    // Buiten productie gaat alles als debug-event naar GA4: zichtbaar in DebugView en
    // uit te sluiten met het datafilter 'Ontwikkelaarsverkeer'. Een testbetaling van
    // Mollie (mode 'test') wordt ook in productie als debug-event gestuurd.
    'debug' => (bool) env('GA4_DEBUG', env('APP_ENV', 'production') !== 'production'),

    // Zo lang houdt een bevestigingspagina het recht om de aankoop te melden.
    // Komt binnen die tijd geen bevestiging uit de browser (gtag geblokkeerd,
    // netwerkfout, tab gesloten), dan mag een later bezoek het opnieuw proberen.
    'purchase_claim_minutes' => 10,

];
