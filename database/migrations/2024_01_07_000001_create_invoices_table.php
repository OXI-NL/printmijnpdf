<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique(); // PMF-2026-0001
            $table->date('invoice_date');

            // Bedragen in centen
            $table->unsignedInteger('amount_excl_btw');   // Bedrag exclusief BTW
            $table->unsignedInteger('amount_btw');         // BTW bedrag
            $table->unsignedInteger('amount_incl_btw');    // Bedrag inclusief BTW
            $table->decimal('btw_percentage', 5, 2)->default(21.00);

            // Individuele regels excl BTW (voor specificatie)
            $table->unsignedInteger('startup_excl_btw');
            $table->unsignedInteger('pages_excl_btw');
            $table->unsignedInteger('binding_excl_btw');
            $table->unsignedInteger('shipping_excl_btw');

            // Mollie fee (voor eigen administratie, niet op klantfactuur)
            $table->unsignedInteger('mollie_fee')->nullable(); // in centen
            $table->unsignedInteger('net_received')->nullable(); // in centen

            // Bedrijfsgegevens snapshot (zodat factuur altijd klopt, ook na wijziging config)
            $table->string('company_name');
            $table->string('company_address');
            $table->string('company_postcode');
            $table->string('company_city');
            $table->string('kvk_number');
            $table->string('btw_id');

            // Klantgegevens snapshot
            $table->string('customer_name');
            $table->string('customer_email');
            $table->text('customer_address');

            // PDF opslag
            $table->string('pdf_path')->nullable();

            $table->timestamps();

            $table->index('invoice_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
