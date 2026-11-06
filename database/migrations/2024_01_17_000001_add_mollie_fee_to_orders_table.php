<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Transactiekosten zoals Mollie ze op de balance inhoudt (balance transaction
            // "deductions"), in centen. Null = nog niet bekend bij Mollie of nog niet opgehaald.
            $table->unsignedInteger('mollie_fee')->nullable()->after('price_total');
            $table->unsignedInteger('mollie_fee_vat')->nullable()->after('mollie_fee');
            // Wat er na aftrek van de kosten op de balance kwam (resultAmount), in centen
            $table->integer('mollie_net_received')->nullable()->after('mollie_fee_vat');
            $table->string('mollie_balance_transaction_id', 40)->nullable()->after('mollie_net_received');
            $table->timestamp('mollie_fee_synced_at')->nullable()->after('mollie_balance_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['mollie_fee', 'mollie_fee_vat', 'mollie_net_received', 'mollie_balance_transaction_id', 'mollie_fee_synced_at']);
        });
    }
};
