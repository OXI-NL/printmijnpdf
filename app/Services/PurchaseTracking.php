<?php

namespace App\Services;

use App\Models\Order;

/**
 * Bepaalt of de bevestigingspagina het GA4-aankoopevent mag sturen.
 *
 * Voorheen stond de enige waarborg in localStorage van de browser. Die is per
 * browser: de bevestigingspagina die Mollie opent in de oorspronkelijke tab én
 * in de browser waarin de bank-app terugkeert, of dezelfde pagina na het wissen
 * van websitegegevens, telde de order opnieuw. Nu beslist de server:
 *
 * 1. claim(): alleen een betaalde order zonder bevestigde meting, en alleen als
 *    er geen verse claim van een andere pagina loopt. Atomair, dus twee gelijktijdige
 *    verzoeken krijgen niet allebei het event.
 * 2. confirm(): de browser meldt dat gtag het event heeft afgehandeld; vanaf dan
 *    stuurt geen pagina dit ordernummer nog als aankoop.
 *
 * Bevestigt de browser niet (gtag geblokkeerd, netwerkfout, tab dicht), dan
 * verloopt de claim en mag een later bezoek het opnieuw proberen.
 */
class PurchaseTracking
{
    public static function claim(Order $order): bool
    {
        if (! $order->isPaid() || $order->analytics_purchase_sent_at !== null) {
            return false;
        }

        $cutoff = now()->subMinutes((int) config('analytics.purchase_claim_minutes', 10));

        $claimed = Order::whereKey($order->id)
            ->whereNull('analytics_purchase_sent_at')
            ->where(fn ($q) => $q->whereNull('analytics_purchase_claimed_at')
                ->orWhere('analytics_purchase_claimed_at', '<', $cutoff))
            ->update(['analytics_purchase_claimed_at' => now()]) === 1;

        $order->refresh();

        return $claimed;
    }

    /** Legt vast dat de aankoop gemeten is; true als dit verzoek dat deed. */
    public static function confirm(Order $order, string $token): bool
    {
        if (! hash_equals(self::token($order), $token) || ! $order->isPaid()) {
            return false;
        }

        return Order::whereKey($order->id)
            ->whereNull('analytics_purchase_sent_at')
            ->update(['analytics_purchase_sent_at' => now()]) === 1;
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
}
