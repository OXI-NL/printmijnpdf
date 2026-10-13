<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Prijsklasse: A5, A4 of A3 (losse pagina's); eigen formaten vallen in de kleinste passende
            $table->string('format', 2)->default('A4')->change();
            // Snijmaat van een afwijkend formaat; leeg bij precies staand A4/A5
            $table->decimal('trim_width_mm', 5, 1)->nullable()->after('format');
            $table->decimal('trim_height_mm', 5, 1)->nullable()->after('trim_width_mm');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['trim_width_mm', 'trim_height_mm']);
            $table->enum('format', ['A4', 'A5'])->default('A4')->change();
        });
    }
};
