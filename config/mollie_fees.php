<?php

/*
 * Mollie-transactiekosten per order (eigen administratie).
 *
 * Mollie zet de kosten van een betaling niet op het payment-object, maar houdt ze
 * in op de balance ("deductions" van de balance transaction). De Balances API is
 * alleen bereikbaar met een organization access token met het recht balances.read;
 * de gewone live_-sleutel (MOLLIE_KEY) werkt daar niet.
 */
return [
    // Organization access token (Mollie-dashboard → Developers → Organization access tokens), alleen balances.read
    'org_token' => env('MOLLIE_ORG_TOKEN'),

    // Mollie B.V. rekent Nederlandse btw over de kosten. De bedragen die Mollie op de
    // balance inhoudt zijn inclusief die btw (de ingehouden "fee-prepayment" bestaat uit
    // fee + fee-vat). Zet op false als de Mollie-factuur laat zien dat het anders is.
    'vat_included' => (bool) env('MOLLIE_FEE_VAT_INCLUDED', true),
    'vat_percentage' => (int) env('MOLLIE_FEE_VAT_PERCENTAGE', 21),
];
