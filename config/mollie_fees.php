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

    // Mollie B.V. rekent 21% Nederlandse btw over de kosten en houdt ze inclusief btw in.
    // Gecontroleerd op de Mollie-facturen van sep/okt 2026: iDEAL-tarief € 0,32 excl.,
    // ingehouden € 0,39 per betaling (sep: 1 betaling, "al voldaan" € 0,39 = 0,32 + 0,07 btw).
    // Het verschil tussen de per transactie afgeronde inhoudingen en de factuur verrekent
    // Mollie als "invoice compensation"; voor de btw-aangifte is de Mollie-factuur leidend.
    'vat_included' => (bool) env('MOLLIE_FEE_VAT_INCLUDED', true),
    'vat_percentage' => (int) env('MOLLIE_FEE_VAT_PERCENTAGE', 21),
];
