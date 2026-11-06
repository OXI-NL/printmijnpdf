<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Meetstatus van het GA4-aankoopevent per order. Drie dingen blijven gescheiden:
 * - wat de browser meldt (handled_at): alleen dat gtag het event afhandelde,
 *   geen bewijs dat Google het ontving;
 * - wat er vóór deze meting al was (legacy): onbekend, en daarom niet opnieuw sturen;
 * - wat in een GA4-export is teruggevonden (ga4_count / ga4_checked_at).
 * Zie docs/analytics-meting.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Lease: de bevestigingspagina die het event mag sturen. Verloopt na
            // config('analytics.purchase_claim_minutes').
            $table->timestamp('analytics_purchase_claimed_at')->nullable();
            // De browser meldt dat gtag het event heeft afgehandeld (event_callback).
            // Stopt verdere browserpogingen; zegt niets over ontvangst bij Google.
            $table->timestamp('analytics_purchase_handled_at')->nullable();
            // Order bestond al vóór deze meting. Of de oude code de aankoop mat, is
            // onbekend; opnieuw sturen kan dubbel tellen, dus de pagina stuurt niets.
            $table->boolean('analytics_purchase_legacy')->default(false);
            // Uit een geïmporteerde GA4-export (analytics:import-ga4): hoe vaak dit
            // transactie-ID als aankoop in GA4 staat. 0 = gecontroleerd en ontbreekt.
            $table->unsignedSmallInteger('analytics_purchase_ga4_count')->nullable();
            $table->timestamp('analytics_purchase_ga4_checked_at')->nullable();
            // 'live' of 'test', zoals Mollie het meldt. Testbetalingen tellen niet
            // mee in de orderrapportage en gaan als debug-event naar GA4.
            $table->string('payment_mode', 10)->nullable();
        });

        // Alleen een onderdrukkingsvlag; geen meetbewijs en geen datum die dat suggereert.
        DB::table('orders')
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->update(['analytics_purchase_legacy' => true]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'analytics_purchase_claimed_at',
                'analytics_purchase_handled_at',
                'analytics_purchase_legacy',
                'analytics_purchase_ga4_count',
                'analytics_purchase_ga4_checked_at',
                'payment_mode',
            ]);
        });
    }
};
