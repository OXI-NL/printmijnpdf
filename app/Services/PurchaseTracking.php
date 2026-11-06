<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Schema;

/**
 * Bepaalt of de bevestigingspagina het GA4-aankoopevent mag sturen, en houdt
 * per order drie dingen gescheiden (docs/analytics-meting.md):
 *
 * 1. Deduplicatie in de browser: claim() geeft het event alleen aan één pagina
 *    tegelijk (atomair, lease van 10 minuten). handled() legt vast dat de browser
 *    meldde dat gtag het event afhandelde; daarna stuurt geen pagina het nog.
 *    Dat is géén ontvangstbewijs: gtag roept event_callback ook aan na een
 *    time-out of als alleen het verzoek naar Google geblokkeerd is.
 * 2. Legacy: orders van vóór deze meting. Onbekend of ze gemeten zijn; de pagina
 *    stuurt ze niet opnieuw om dubbeltelling te voorkomen.
 * 3. Ontvangst in GA4: alleen vast te stellen uit een GA4-export van
 *    transactie-ID's (php artisan analytics:import-ga4).
 */
class PurchaseTracking
{
    public const COLUMNS = [
        'analytics_purchase_claimed_at',
        'analytics_purchase_handled_at',
        'analytics_purchase_legacy',
        'analytics_purchase_ga4_count',
        'analytics_purchase_ga4_checked_at',
        'payment_mode',
    ];

    /**
     * Staat de migratie er al? Tijdens een uitrol kan de nieuwe code heel even
     * draaien vóór `migrate`. Dan geen meting, maar de betaling en webhook
     * werken gewoon door.
     */
    public static function ready(): bool
    {
        return Schema::hasColumns('orders', self::COLUMNS);
    }

    public static function claim(Order $order): bool
    {
        if (! $order->isPaid()
            || $order->analytics_purchase_legacy
            || $order->analytics_purchase_handled_at !== null
            || $order->analytics_purchase_ga4_count > 0) {
            return false;
        }

        $cutoff = now()->subMinutes((int) config('analytics.purchase_claim_minutes', 10));

        $claimed = Order::whereKey($order->id)
            ->where('analytics_purchase_legacy', false)
            ->whereNull('analytics_purchase_handled_at')
            ->where(fn ($q) => $q->whereNull('analytics_purchase_claimed_at')
                ->orWhere('analytics_purchase_claimed_at', '<', $cutoff))
            ->update(['analytics_purchase_claimed_at' => now()]) === 1;

        $order->refresh();

        return $claimed;
    }

    /**
     * De browser meldt dat gtag het event heeft afgehandeld. Stopt verdere
     * browserpogingen. True als dit verzoek dat vastlegde.
     */
    public static function handled(Order $order, string $token): bool
    {
        if (! hash_equals(self::token($order), $token) || ! $order->isPaid()) {
            return false;
        }

        return Order::whereKey($order->id)
            ->whereNull('analytics_purchase_handled_at')
            ->update(['analytics_purchase_handled_at' => now()]) === 1;
    }

    /** Alleen de pagina die de server zelf rendert, kent dit token. */
    public static function token(Order $order): string
    {
        return hash_hmac('sha256', 'ga4-purchase|'.$order->order_number, (string) config('app.key'));
    }

    /**
     * Gegevens voor gtag('event', 'purchase', ...). Bedragen in euro's, zoals de
     * order in het bestelbeheer staat (inclusief btw, na korting).
     */
    public static function eventParams(Order $order): array
    {
        $params = [
            'transaction_id' => $order->order_number,
            'currency' => 'EUR',
            'value' => round($order->price_total / 100, 2),
            'shipping' => round(($order->price_shipping ?? 0) / 100, 2),
            'items' => [[
                'item_id' => $order->binding_type === 'booklet' ? 'booklet' : 'loose',
                'item_name' => $order->binding_type === 'booklet' ? 'Geniet boekje' : 'Losse pagina\'s',
                'item_variant' => $order->format,
                'quantity' => (int) ($order->quantity ?? 1),
                'price' => round(($order->price_total - ($order->price_shipping ?? 0)) / 100 / max(1, (int) ($order->quantity ?? 1)), 2),
            ]],
            'delivery_type' => $order->delivery_type,
        ];

        if ($order->promo_code) {
            $params['coupon'] = $order->promo_code;
        }

        return $params;
    }

    public static function isTestOrder(Order $order): bool
    {
        return $order->payment_mode === 'test';
    }

    /** Kolom 'Browser' in orders:report. */
    public static function browserLabel(Order $order): string
    {
        return match (true) {
            (bool) $order->analytics_purchase_legacy => 'vóór meting',
            $order->analytics_purchase_handled_at !== null => 'afgehandeld',
            default => 'nee',
        };
    }

    /** Kolom 'GA4-export' in orders:report. Leeg = niet gecontroleerd. */
    public static function ga4Label(Order $order): string
    {
        $count = $order->analytics_purchase_ga4_count;

        return match (true) {
            $count === null => '',
            $count === 0 => 'ontbreekt',
            $count === 1 => '1×',
            default => $count.'× (dubbel)',
        };
    }
}
