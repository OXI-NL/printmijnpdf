<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Label via de PostNL API; track_trace wordt pas gezet als de admin de verzending bevestigt
            $table->string('postnl_barcode', 20)->nullable()->after('track_trace');
            $table->string('postnl_product', 30)->nullable()->after('postnl_barcode');
            $table->string('postnl_label_path')->nullable()->after('postnl_product');
            $table->timestamp('postnl_label_created_at')->nullable()->after('postnl_label_path');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['postnl_barcode', 'postnl_product', 'postnl_label_path', 'postnl_label_created_at']);
        });
    }
};
