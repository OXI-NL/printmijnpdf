<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            
            // Mollie payment
            $table->string('mollie_payment_id')->nullable()->index();
            $table->enum('status', [
                'pending',      // Wacht op betaling
                'paid',         // Betaald, wacht op productie
                'processing',   // In productie
                'shipped',      // Verzonden
                'delivered',    // Afgeleverd
                'cancelled',    // Geannuleerd
                'refunded'      // Terugbetaald
            ])->default('pending');
            
            // PDF gegevens
            $table->string('pdf_original_name');
            $table->string('pdf_stored_name');
            $table->string('pdf_path');
            $table->integer('page_count');
            $table->enum('format', ['A4', 'A5'])->default('A4');
            $table->boolean('has_bleed')->default(false);
            $table->integer('bleed_mm')->nullable();
            $table->enum('binding_type', ['booklet', 'loose'])->default('booklet');
            $table->enum('print_side', ['single', 'double'])->default('double');
            
            // Prijzen (in centen)
            $table->integer('price_startup');
            $table->integer('price_pages');
            $table->integer('price_binding');
            $table->integer('price_shipping');
            $table->integer('price_total');
            
            // Klantgegevens
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            
            // Verzendadres
            $table->string('address_street');
            $table->string('address_number');
            $table->string('address_addition')->nullable();
            $table->string('address_postcode');
            $table->string('address_city');
            $table->string('address_country')->default('NL');
            
            // Verzending
            $table->string('track_trace')->nullable();
            $table->timestamp('shipped_at')->nullable();
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Indexen
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
