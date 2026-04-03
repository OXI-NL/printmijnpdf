<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('delivery_type');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 255)->nullable()->after('utm_medium');
            $table->string('utm_term', 255)->nullable()->after('utm_campaign');
            $table->string('utm_content', 255)->nullable()->after('utm_term');
            $table->string('gclid', 255)->nullable()->after('utm_content');
            $table->string('fbclid', 255)->nullable()->after('gclid');
            $table->string('msclkid', 255)->nullable()->after('fbclid');
            $table->string('landing_page', 255)->nullable()->after('msclkid');
            $table->string('referrer', 500)->nullable()->after('landing_page');

            $table->index(['utm_source', 'utm_medium'], 'orders_attribution_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_attribution_index');
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'gclid', 'fbclid', 'msclkid', 'landing_page', 'referrer',
            ]);
        });
    }
};
