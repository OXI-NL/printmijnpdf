<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            // Kosten die Mollie voor de terugstorting inhoudt (refund-balance transaction), in centen.
            // Tellen mee in de maand van de creditnota, niet in die van de order.
            $table->unsignedInteger('mollie_fee')->nullable()->after('btw_percentage');
            $table->unsignedInteger('mollie_fee_vat')->nullable()->after('mollie_fee');
            $table->string('mollie_balance_transaction_id', 40)->nullable()->after('mollie_fee_vat');
            $table->timestamp('mollie_fee_synced_at')->nullable()->after('mollie_balance_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn(['mollie_fee', 'mollie_fee_vat', 'mollie_balance_transaction_id', 'mollie_fee_synced_at']);
        });
    }
};
