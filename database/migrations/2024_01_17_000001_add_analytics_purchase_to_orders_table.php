<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // De bevestigingspagina die het GA4-aankoopevent mag sturen, claimt het
            // eerst. Een tweede tab of browser binnen de claimtermijn krijgt geen event.
            $table->timestamp('analytics_purchase_claimed_at')->nullable()->after('payment_failed_mailed_at');
            // Gezet zodra de browser meldt dat gtag het event heeft afgehandeld.
            // Daarna stuurt geen enkele pagina dit ordernummer nog als aankoop.
            $table->timestamp('analytics_purchase_sent_at')->nullable()->after('analytics_purchase_claimed_at');
            // 'live' of 'test', zoals Mollie het meldt. Testbetalingen tellen niet
            // mee in de orderrapportage en gaan als debug-event naar GA4.
            $table->string('payment_mode', 10)->nullable()->after('mollie_payment_id');
        });

        // Bestaande betaalde orders zijn al (één of meer keer) als aankoop gemeten.
        // Zonder deze regel zou een klant die zijn oude bevestigingspagina opent,
        // de aankoop na de uitrol nog één keer laten tellen.
        DB::table('orders')
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->update(['analytics_purchase_sent_at' => DB::raw('COALESCE(paid_at, updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['analytics_purchase_claimed_at', 'analytics_purchase_sent_at', 'payment_mode']);
        });
    }
};
