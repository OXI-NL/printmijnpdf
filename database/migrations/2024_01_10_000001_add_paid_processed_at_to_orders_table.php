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
            // Moment waarop impositie + bevestigingsmails na betaling zijn afgehandeld
            $table->timestamp('paid_processed_at')->nullable()->after('paid_at');
            // Moment waarop de mail over een mislukte betaling is verstuurd
            $table->timestamp('payment_failed_mailed_at')->nullable()->after('paid_processed_at');
        });

        // Bestaande orders gelden als afgehandeld, zodat een latere webhook
        // (bv. bij een terugbetaling) oude klanten niet alsnog mailt.
        DB::table('orders')
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->update(['paid_processed_at' => DB::raw('COALESCE(paid_at, updated_at, created_at)')]);

        // Idem voor geannuleerde orders: die krijgen geen (nieuwe) mislukt-mail meer
        DB::table('orders')
            ->where('status', 'cancelled')
            ->update(['payment_failed_mailed_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_processed_at', 'payment_failed_mailed_at']);
        });
    }
};
