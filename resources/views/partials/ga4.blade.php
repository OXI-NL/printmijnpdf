{{--
    Google Analytics 4. Eén plek voor het meet-ID en de debugstand.
    $gaDebug (optioneel): stuur alle events van deze pagina als debug-event,
    bv. voor een testbetaling van Mollie. Debug-events zie je in DebugView en
    sluit het datafilter 'Ontwikkelaarsverkeer' uit de rapporten.
    Eventdefinities: docs/analytics-meting.md
--}}
@php
    $gaId = config('analytics.ga4_measurement_id');
    $gaDebugMode = config('analytics.debug') || ! empty($gaDebug);
@endphp
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
</script>
@if($gaId)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>
    gtag('js', new Date());
    gtag('config', @json($gaId){!! $gaDebugMode ? ", {debug_mode: true}" : '' !!});
</script>
@endif
