<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eén creditnota per Mollie-terugbetaling. De order zelf houdt zijn status:
        // een terugbetaalde order kan gewoon nog gedrukt en verzonden worden.
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('credit_number')->unique(); // C-PMP-261028-0001
            $table->string('mollie_refund_id')->unique(); // re_... voorkomt dubbele creditnota's
            $table->date('credit_date');

            // Bedragen in centen, positief opgeslagen; op de pdf en in overzichten negatief
            $table->unsignedInteger('amount_excl_btw');
            $table->unsignedInteger('amount_btw');
            $table->unsignedInteger('amount_incl_btw');
            $table->decimal('btw_percentage', 5, 2)->default(21.00);

            $table->string('description')->nullable(); // omschrijving uit Mollie

            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index('credit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
