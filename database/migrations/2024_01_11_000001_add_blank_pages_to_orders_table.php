<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Blanco pagina's die aan het eind van een boekje worden toegevoegd (veelvoud van 4)
            $table->unsignedTinyInteger('blank_pages')->default(0)->after('page_count');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('blank_pages');
        });
    }
};
