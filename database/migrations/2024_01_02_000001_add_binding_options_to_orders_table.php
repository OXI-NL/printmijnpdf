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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'binding_type')) {
                $table->enum('binding_type', ['booklet', 'loose'])->default('booklet')->after('bleed_mm');
            }
            if (!Schema::hasColumn('orders', 'print_side')) {
                $table->enum('print_side', ['single', 'double'])->default('double')->after('binding_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['binding_type', 'print_side']);
        });
    }
};
