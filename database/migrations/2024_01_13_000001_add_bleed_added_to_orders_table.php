<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Afloop door ons aangevuld (gespiegelde rand) bij een PDF zonder afloop
            $table->boolean('bleed_added')->default(false)->after('bleed_mm');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('bleed_added');
        });
    }
};
